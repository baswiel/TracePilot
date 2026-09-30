<?php

namespace App\Actions;

use App\Models\BusinessHours;
use App\Models\Issue;
use Carbon\CarbonInterface;

class CalculateIssueSla
{
    private ?BusinessHours $businessHours = null;

    /**
     * @return array{response: array{target_minutes: int|null, deadline_at: string|null, state: string, label: string, remaining_minutes: int|null}, resolution: array{target_minutes: int|null, deadline_at: string|null, state: string, label: string, remaining_minutes: int|null}, needs_attention: bool}
     */
    public function handle(Issue $issue, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $target = $issue->project->slaLevel
            ?->targets()
            ->where('priority', $issue->priority)
            ->first();
        $responseMinutes = $issue->project->sla_level_id === null
            ? $issue->project->sla_first_response_minutes
            : $target->response_minutes;
        $resolutionMinutes = $issue->project->sla_level_id === null
            ? $issue->project->sla_resolution_minutes
            : $target->resolution_minutes;
        $response = $this->milestone(
            $issue->reported_at,
            $responseMinutes,
            $issue->first_responded_at,
            $now,
            'reactie',
        );
        $resolution = $this->milestone(
            $issue->reported_at,
            $resolutionMinutes,
            $issue->resolved_at,
            $now,
            'oplossing',
        );

        return [
            'response' => $response,
            'resolution' => $resolution,
            'needs_attention' => in_array($response['state'], ['at_risk', 'overdue'])
                || in_array($resolution['state'], ['at_risk', 'overdue']),
        ];
    }

    /**
     * @return array{target_minutes: int|null, deadline_at: string|null, state: string, label: string, remaining_minutes: int|null}
     */
    private function milestone(
        CarbonInterface $reportedAt,
        ?int $targetMinutes,
        ?CarbonInterface $achievedAt,
        CarbonInterface $now,
        string $name,
    ): array {
        if ($targetMinutes === null) {
            return $this->data(null, null, 'unavailable', "Geen SLA voor {$name}", null);
        }

        $deadline = $this->addBusinessMinutes($reportedAt, $targetMinutes);
        $reference = $achievedAt ?? $now;
        $remainingMinutes = $targetMinutes - $this->businessMinutesBetween($reportedAt, $reference);

        if ($achievedAt !== null) {
            return $this->data(
                $targetMinutes,
                $deadline,
                $remainingMinutes < 0 ? 'breached' : 'met',
                $remainingMinutes < 0 ? ucfirst($name).' te laat' : ucfirst($name).' binnen SLA',
                $remainingMinutes,
            );
        }

        if ($remainingMinutes < 0) {
            return $this->data($targetMinutes, $deadline, 'overdue', ucfirst($name).' overschreden', $remainingMinutes);
        }

        if ($remainingMinutes <= max(15, (int) ceil($targetMinutes * 0.25))) {
            return $this->data($targetMinutes, $deadline, 'at_risk', ucfirst($name).' bijna verlopen', $remainingMinutes);
        }

        return $this->data($targetMinutes, $deadline, 'on_track', ucfirst($name).' op schema', $remainingMinutes);
    }

    /**
     * @return array{target_minutes: int|null, deadline_at: string|null, state: string, label: string, remaining_minutes: int|null}
     */
    private function data(?int $targetMinutes, ?CarbonInterface $deadline, string $state, string $label, ?int $remainingMinutes): array
    {
        return [
            'target_minutes' => $targetMinutes,
            'deadline_at' => $deadline?->toDateTimeString(),
            'state' => $state,
            'label' => $label,
            'remaining_minutes' => $remainingMinutes,
        ];
    }

    private function addBusinessMinutes(CarbonInterface $from, int $minutes): CarbonInterface
    {
        $cursor = $this->nextBusinessMoment($from);

        while ($minutes > 0) {
            $endOfDay = $this->endOfBusinessDay($cursor);
            $availableMinutes = (int) $cursor->diffInMinutes($endOfDay);

            if ($minutes <= $availableMinutes) {
                return $cursor->addMinutes($minutes);
            }

            $minutes -= $availableMinutes;
            $cursor = $this->nextBusinessMoment($cursor->addDay()->startOfDay());
        }

        return $cursor;
    }

    private function businessMinutesBetween(CarbonInterface $from, CarbonInterface $until): int
    {
        if ($from->equalTo($until)) {
            return 0;
        }

        if ($from->greaterThan($until)) {
            return -$this->businessMinutesBetween($until, $from);
        }

        $cursor = $this->nextBusinessMoment($from);
        $minutes = 0;

        while ($cursor->lessThan($until)) {
            $endOfDay = $this->endOfBusinessDay($cursor);
            $intervalEnd = $endOfDay->lessThan($until) ? $endOfDay : $until;
            $minutes += (int) $cursor->diffInMinutes($intervalEnd);

            if ($intervalEnd->equalTo($until)) {
                break;
            }

            $cursor = $this->nextBusinessMoment($cursor->addDay()->startOfDay());
        }

        return $minutes;
    }

    private function nextBusinessMoment(CarbonInterface $moment): CarbonInterface
    {
        $cursor = $moment->copy();

        while (! $this->isWorkingDay($cursor)) {
            $cursor = $cursor->addDay()->startOfDay();
        }

        $startOfDay = $this->startOfBusinessDay($cursor);
        $endOfDay = $this->endOfBusinessDay($cursor);

        if ($cursor->lessThan($startOfDay)) {
            return $startOfDay;
        }

        if ($cursor->greaterThanOrEqualTo($endOfDay)) {
            return $this->nextBusinessMoment($cursor->addDay()->startOfDay());
        }

        return $cursor;
    }

    private function isWorkingDay(CarbonInterface $date): bool
    {
        return in_array($date->isoWeekday(), $this->businessHours()->working_days, true);
    }

    private function startOfBusinessDay(CarbonInterface $date): CarbonInterface
    {
        return $date->copy()->startOfDay()->setTimeFromTimeString($this->businessHours()->starts_at);
    }

    private function endOfBusinessDay(CarbonInterface $date): CarbonInterface
    {
        return $date->copy()->startOfDay()->setTimeFromTimeString($this->businessHours()->ends_at);
    }

    private function businessHours(): BusinessHours
    {
        return $this->businessHours ??= BusinessHours::query()->firstOrCreate(
            ['id' => 1],
            ['working_days' => [1, 2, 3, 4, 5], 'starts_at' => '09:00', 'ends_at' => '17:00'],
        );
    }
}
