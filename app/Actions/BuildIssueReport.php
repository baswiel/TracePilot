<?php

namespace App\Actions;

use App\Enums\IssueCause;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Support\IssueReportPeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\LazyCollection;

/**
 * @phpstan-type Totals array{reported: int, completed: int, response_sum: int, response_count: int, resolution_sum: int, resolution_count: int}
 * @phpstan-type Summary array{reported: int, completed: int, active: int, average_first_response_minutes: int|null, average_resolution_minutes: int|null}
 * @phpstan-type SlaSummary array{tracked: int, met: int, breached: int, percentage: int|null}
 * @phpstan-type TrendBin array{from: CarbonInterface, until: CarbonInterface, label: string, reported: int}
 */
class BuildIssueReport
{
    public function __construct(private readonly CalculateIssueSla $calculateIssueSla) {}

    /** @return array<string, mixed> */
    public function handle(IssueReportPeriod $period, ?int $projectId = null): array
    {
        $current = $this->collectPeriod($period, $projectId);
        $previous = $this->summaryFor($period->previous(), $projectId);
        $current['comparison'] = [];
        foreach (['reported', 'completed', 'average_first_response_minutes', 'average_resolution_minutes'] as $field) {
            $current['comparison'][$field] = $this->percentageChange($current['summary'][$field], $previous[$field]);
        }

        return $current;
    }

    /** @return Summary */
    private function summaryFor(IssueReportPeriod $period, ?int $projectId): array
    {
        $totals = $this->emptyTotals();
        foreach ($this->issuesFor($period, $projectId, false) as $issue) {
            $totals = $this->addIssue($totals, $issue);
        }

        return $this->summary($totals);
    }

    /** @return array{summary: Summary, sla: array{response: SlaSummary, resolution: SlaSummary}, priorities: list<array{priority: string, reported: int, completed: int, average_resolution_minutes: int|null}>, causes: list<array{cause: string, label: string, count: int, percentage: int}>, projects: list<array{id: int, name: string, reported: int, completed: int, average_resolution_minutes: int|null, sla_percentage: int|null}>, trend: array{granularity: string, points: list<array{label: string, reported: int}>}} */
    private function collectPeriod(IssueReportPeriod $period, ?int $projectId): array
    {
        $totals = $this->emptyTotals();
        $response = ['met' => 0, 'breached' => 0];
        $resolution = ['met' => 0, 'breached' => 0];
        $priorities = [];
        foreach (IssuePriority::cases() as $priority) {
            $priorities[$priority->value] = $this->emptyTotals();
        }
        $projects = [];
        $causes = [];
        $trend = $this->trendBins($period);

        foreach ($this->issuesFor($period, $projectId, true) as $issue) {
            $totals = $this->addIssue($totals, $issue);
            $priorities[$issue->priority->value] = $this->addIssue($priorities[$issue->priority->value], $issue);
            $projects[$issue->project_id] ??= [
                'id' => $issue->project_id, 'name' => $issue->project->name,
                'totals' => $this->emptyTotals(), 'met' => 0, 'breached' => 0,
            ];
            $projects[$issue->project_id]['totals'] = $this->addIssue($projects[$issue->project_id]['totals'], $issue);
            $sla = $this->calculateIssueSla->handle($issue);
            if (in_array($sla['response']['state'], ['met', 'breached'], true)) {
                $response[$sla['response']['state']]++;
            }
            if (in_array($sla['resolution']['state'], ['met', 'breached'], true)) {
                $resolution[$sla['resolution']['state']]++;
                $projects[$issue->project_id][$sla['resolution']['state']]++;
            }
            if ($issue->cause !== null) {
                $causes[$issue->cause->value] = ($causes[$issue->cause->value] ?? 0) + 1;
            }
            foreach ($trend as &$bin) {
                if ($issue->reported_at->betweenIncluded($bin['from'], $bin['until'])) {
                    $bin['reported']++;
                    break;
                }
            }
            unset($bin);
        }

        $priorityRows = [];
        foreach ($priorities as $priority => $counts) {
            $summary = $this->summary($counts);
            $priorityRows[] = ['priority' => $priority, 'reported' => $summary['reported'], 'completed' => $summary['completed'], 'average_resolution_minutes' => $summary['average_resolution_minutes']];
        }
        $projectRows = [];
        foreach ($projects as $project) {
            $summary = $this->summary($project['totals']);
            $projectRows[] = ['id' => $project['id'], 'name' => $project['name'], 'reported' => $summary['reported'], 'completed' => $summary['completed'], 'average_resolution_minutes' => $summary['average_resolution_minutes'], 'sla_percentage' => $this->slaSummary($project['met'], $project['breached'])['percentage']];
        }
        usort($projectRows, fn (array $a, array $b): int => $b['reported'] <=> $a['reported']);
        $causeRows = [];
        $classified = array_sum($causes);
        foreach (IssueCause::cases() as $cause) {
            $count = $causes[$cause->value] ?? 0;
            if ($count > 0) {
                $causeRows[] = ['cause' => $cause->value, 'label' => $cause->label(), 'count' => $count, 'percentage' => (int) round($count / $classified * 100)];
            }
        }
        usort($causeRows, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return [
            'summary' => $this->summary($totals),
            'sla' => ['response' => $this->slaSummary($response['met'], $response['breached']), 'resolution' => $this->slaSummary($resolution['met'], $resolution['breached'])],
            'priorities' => $priorityRows, 'causes' => $causeRows, 'projects' => $projectRows,
            'trend' => ['granularity' => $period->granularity(), 'points' => array_map(fn (array $bin): array => ['label' => $bin['label'], 'reported' => $bin['reported']], $trend)],
        ];
    }

    /** @return LazyCollection<int, Issue> */
    private function issuesFor(IssueReportPeriod $period, ?int $projectId, bool $withSla): LazyCollection
    {
        return Issue::query()
            ->select(['id', 'project_id', 'priority', 'status', 'reported_at', 'first_responded_at', 'resolved_at', 'cause'])
            ->whereBetween('reported_at', [$period->from, $period->until])
            ->when($projectId, fn ($query, int $id) => $query->where('project_id', $id))
            ->when($withSla, fn ($query) => $query->with('project.slaLevel.targets'))
            ->lazyById(250);
    }

    /** @return Totals */
    private function emptyTotals(): array
    {
        return ['reported' => 0, 'completed' => 0, 'response_sum' => 0, 'response_count' => 0, 'resolution_sum' => 0, 'resolution_count' => 0];
    }

    /** @param Totals $totals
     * @return Totals
     */
    private function addIssue(array $totals, Issue $issue): array
    {
        $totals['reported']++;
        $totals['completed'] += $issue->status === IssueStatus::Completed ? 1 : 0;
        if ($issue->first_responded_at !== null) {
            $totals['response_sum'] += (int) $issue->reported_at->diffInMinutes($issue->first_responded_at);
            $totals['response_count']++;
        }
        if ($issue->resolved_at !== null) {
            $totals['resolution_sum'] += (int) $issue->reported_at->diffInMinutes($issue->resolved_at);
            $totals['resolution_count']++;
        }

        return $totals;
    }

    /** @param Totals $totals
     * @return Summary
     */
    private function summary(array $totals): array
    {
        return ['reported' => $totals['reported'], 'completed' => $totals['completed'], 'active' => $totals['reported'] - $totals['completed'], 'average_first_response_minutes' => $totals['response_count'] === 0 ? null : (int) round($totals['response_sum'] / $totals['response_count']), 'average_resolution_minutes' => $totals['resolution_count'] === 0 ? null : (int) round($totals['resolution_sum'] / $totals['resolution_count'])];
    }

    /** @return SlaSummary */
    private function slaSummary(int $met, int $breached): array
    {
        $tracked = $met + $breached;

        return ['tracked' => $tracked, 'met' => $met, 'breached' => $breached, 'percentage' => $tracked === 0 ? null : (int) round($met / $tracked * 100)];
    }

    private function percentageChange(?int $current, ?int $previous): ?int
    {
        return $current === null || $previous === null || $previous === 0 ? null : (int) round(($current - $previous) / $previous * 100);
    }

    /** @return list<TrendBin> */
    private function trendBins(IssueReportPeriod $period): array
    {
        $cursor = $period->from->copy();
        $bins = [];
        $granularity = $period->granularity();
        while ($cursor->lte($period->until)) {
            $end = match ($granularity) {
                'week' => $cursor->copy()->addDays(6)->endOfDay(),
                'month' => $cursor->copy()->endOfMonth(),
                default => $cursor->copy()->endOfDay(),
            };
            $bins[] = ['from' => $cursor->copy(), 'until' => $end->gt($period->until) ? $period->until->copy() : $end, 'label' => $cursor->isoFormat($granularity === 'month' ? 'MMM YYYY' : 'D MMM'), 'reported' => 0];
            $cursor = match ($granularity) {
                'week' => $cursor->addDays(7)->startOfDay(),
                'month' => $cursor->addMonthNoOverflow()->startOfMonth(),
                default => $cursor->addDay()->startOfDay(),
            };
        }

        return $bins;
    }
}
