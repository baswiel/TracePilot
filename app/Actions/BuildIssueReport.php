<?php

namespace App\Actions;

use App\Enums\IssueCause;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Support\IssueReportPeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * @phpstan-type Milestone array{target_minutes: int|null, deadline_at: string|null, state: string, label: string, remaining_minutes: int|null}
 * @phpstan-type SlaState array{response: Milestone, resolution: Milestone, needs_attention: bool}
 * @phpstan-type SlaSummary array{tracked: int, met: int, breached: int, percentage: int|null}
 * @phpstan-type PrioritySummary array{priority: string, reported: int, completed: int, average_resolution_minutes: int|null}
 * @phpstan-type ProjectSummary array{id: int, name: string, reported: int, completed: int, average_resolution_minutes: int|null, sla_percentage: int|null}
 * @phpstan-type CauseSummary array{cause: string, label: string, count: int, percentage: int}
 */
class BuildIssueReport
{
    public function __construct(private readonly CalculateIssueSla $calculateIssueSla) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(IssueReportPeriod $period, ?int $projectId = null): array
    {
        $issues = $this->issuesFor($period->from, $period->until, $projectId);
        $previousIssues = $this->issuesFor($period->previous()->from, $period->previous()->until, $projectId);

        $slaStates = $issues->mapWithKeys(
            fn (Issue $issue): array => [$issue->id => $this->calculateIssueSla->handle($issue)],
        )->all();

        return [
            'summary' => $this->summary($issues),
            'comparison' => $this->comparison($issues, $previousIssues),
            'sla' => [
                'response' => $this->slaSummary($issues, $slaStates, 'response'),
                'resolution' => $this->slaSummary($issues, $slaStates, 'resolution'),
            ],
            'trend' => $this->trend($issues, $period),
            'priorities' => collect(IssuePriority::cases())->map(
                fn (IssuePriority $priority): array => $this->prioritySummary(
                    $issues->where('priority', $priority),
                    $priority,
                ),
            )->all(),
            'causes' => $this->causeSummary($issues),
            'projects' => $issues
                ->groupBy('project_id')
                ->map(fn (Collection $projectIssues): array => $this->projectSummary($projectIssues, $slaStates))
                ->sortByDesc('reported')
                ->values()
                ->all(),
        ];
    }

    /** @return Collection<int, Issue> */
    private function issuesFor(CarbonInterface $from, CarbonInterface $until, ?int $projectId): Collection
    {
        return Issue::query()
            ->whereBetween('reported_at', [$from, $until])
            ->when($projectId, fn ($query, int $id) => $query->where('project_id', $id))
            ->with('project.slaLevel.targets')
            ->get();
    }

    /** @param Collection<int, Issue> $issues */
    private function summary(Collection $issues): array
    {
        return [
            'reported' => $issues->count(),
            'completed' => $issues->where('status', IssueStatus::Completed)->count(),
            'active' => $issues->where('status', '!=', IssueStatus::Completed)->count(),
            'average_first_response_minutes' => $this->averageDuration($issues, 'first_responded_at'),
            'average_resolution_minutes' => $this->averageDuration($issues, 'resolved_at'),
        ];
    }

    /** @param Collection<int, Issue> $issues @param Collection<int, Issue> $previousIssues */
    private function comparison(Collection $issues, Collection $previousIssues): array
    {
        $current = $this->summary($issues);
        $previous = $this->summary($previousIssues);

        return [
            'reported' => $this->percentageChange($current['reported'], $previous['reported']),
            'completed' => $this->percentageChange($current['completed'], $previous['completed']),
            'average_first_response_minutes' => $this->percentageChange($current['average_first_response_minutes'], $previous['average_first_response_minutes']),
            'average_resolution_minutes' => $this->percentageChange($current['average_resolution_minutes'], $previous['average_resolution_minutes']),
        ];
    }

    private function percentageChange(?int $current, ?int $previous): ?int
    {
        if ($current === null || $previous === null || $previous === 0) {
            return null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    /** @param Collection<int, Issue> $issues */
    private function trend(Collection $issues, IssueReportPeriod $period): array
    {
        $granularity = $period->granularity();
        $cursor = $period->from->copy();
        $points = [];

        while ($cursor->lte($period->until)) {
            $end = match ($granularity) {
                'week' => $cursor->copy()->addDays(6)->endOfDay(),
                'month' => $cursor->copy()->endOfMonth(),
                default => $cursor->copy()->endOfDay(),
            };
            $end = $end->gt($period->until) ? $period->until->copy() : $end;
            $points[] = [
                'label' => match ($granularity) {
                    'month' => $cursor->isoFormat('MMM YYYY'),
                    'week' => $cursor->isoFormat('D MMM'),
                    default => $cursor->isoFormat('D MMM'),
                },
                'reported' => $issues->filter(fn (Issue $issue): bool => $issue->reported_at->betweenIncluded($cursor, $end))->count(),
            ];
            $cursor = match ($granularity) {
                'week' => $cursor->addDays(7)->startOfDay(),
                'month' => $cursor->addMonthNoOverflow()->startOfMonth(),
                default => $cursor->addDay()->startOfDay(),
            };
        }

        return ['granularity' => $granularity, 'points' => $points];
    }

    /**
     * @param  Collection<int, Issue>  $issues
     * @return array<int, CauseSummary>
     */
    private function causeSummary(Collection $issues): array
    {
        $classifiedIssues = $issues->filter(fn (Issue $issue): bool => $issue->cause !== null);
        $total = $classifiedIssues->count();

        return collect(IssueCause::cases())
            ->map(function (IssueCause $cause) use ($classifiedIssues, $total): array {
                $count = $classifiedIssues->where('cause', $cause)->count();

                return [
                    'cause' => $cause->value,
                    'label' => $cause->label(),
                    'count' => $count,
                    'percentage' => $total === 0 ? 0 : (int) round(($count / $total) * 100),
                ];
            })
            ->filter(fn (array $item): bool => $item['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /** @param Collection<int, Issue> $issues */
    private function averageDuration(Collection $issues, string $completedAt): ?int
    {
        $durations = $issues
            ->filter(fn (Issue $issue): bool => $issue->{$completedAt} !== null)
            ->map(fn (Issue $issue): int => (int) $issue->reported_at->diffInMinutes($issue->{$completedAt}));

        return $durations->isEmpty() ? null : (int) round($durations->average());
    }

    /**
     * @param  Collection<int, Issue>  $issues
     * @param  array<int, SlaState>  $slaStates
     * @param  'response'|'resolution'  $milestone
     * @return SlaSummary
     */
    private function slaSummary(Collection $issues, array $slaStates, string $milestone): array
    {
        $states = $issues
            ->map(fn (Issue $issue): array => $slaStates[$issue->id][$milestone])
            ->filter(fn (array $sla): bool => in_array($sla['state'], ['met', 'breached']));
        $met = $states->where('state', 'met')->count();

        return [
            'tracked' => $states->count(),
            'met' => $met,
            'breached' => $states->where('state', 'breached')->count(),
            'percentage' => $states->isEmpty() ? null : (int) round(($met / $states->count()) * 100),
        ];
    }

    /**
     * @param  Collection<int, Issue>  $issues
     * @return PrioritySummary
     */
    private function prioritySummary(Collection $issues, IssuePriority $priority): array
    {
        return [
            'priority' => $priority->value,
            'reported' => $issues->count(),
            'completed' => $issues->where('status', IssueStatus::Completed)->count(),
            'average_resolution_minutes' => $this->averageDuration($issues, 'resolved_at'),
        ];
    }

    /**
     * @param  Collection<int, Issue>  $issues
     * @param  array<int, SlaState>  $slaStates
     * @return ProjectSummary
     */
    private function projectSummary(Collection $issues, array $slaStates): array
    {
        /** @var Issue $firstIssue */
        $firstIssue = $issues->first();
        $resolutionSla = $this->slaSummary($issues, $slaStates, 'resolution');

        return [
            'id' => $firstIssue->project->id,
            'name' => $firstIssue->project->name,
            'reported' => $issues->count(),
            'completed' => $issues->where('status', IssueStatus::Completed)->count(),
            'average_resolution_minutes' => $this->averageDuration($issues, 'resolved_at'),
            'sla_percentage' => $resolutionSla['percentage'],
        ];
    }
}
