<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final readonly class IssueReportPeriod
{
    public function __construct(
        public string $key,
        public CarbonInterface $from,
        public CarbonInterface $until,
    ) {}

    public static function fromRequest(?string $key, ?string $from, ?string $to): self
    {
        $now = now();

        if ($key === 'custom' || ($from !== null && $to !== null)) {
            return new self(
                'custom',
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            );
        }

        return match ($key) {
            'week' => new self('week', $now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()),
            'year' => new self('year', $now->copy()->subMonthsNoOverflow(12)->startOfDay(), $now->copy()->endOfDay()),
            default => new self('month', $now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()),
        };
    }

    public function previous(): self
    {
        $days = $this->from->diffInDays($this->until->copy()->startOfDay()) + 1;

        return new self(
            $this->key,
            $this->from->copy()->subDays($days)->startOfDay(),
            $this->from->copy()->subDay()->endOfDay(),
        );
    }

    public function granularity(): string
    {
        $days = $this->from->diffInDays($this->until->copy()->startOfDay()) + 1;

        return $days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month');
    }
}
