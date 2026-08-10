<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Interprets assigned/live demand against base capacity and optional double-park overflow.
 *
 * Zones:
 * - ok: used <= capacity
 * - overflow: capacity < used <= capacity + overflow (double parking)
 * - critical: used > capacity + overflow
 *
 * The recommended target sits at capacity + floor(overflow / 2).
 */
final class CarParkCapacityReading
{
    public function __construct(
        public readonly int $used,
        public readonly int $capacity,
        public readonly int $overflow,
    ) {}

    public static function make(int $used, int $capacity, int $overflow = 0): self
    {
        return new self(
            used: max(0, $used),
            capacity: max(0, $capacity),
            overflow: max(0, $overflow),
        );
    }

    public function hardLimit(): int
    {
        return $this->capacity + $this->overflow;
    }

    public function recommendedLimit(): int
    {
        if ($this->overflow <= 0) {
            return $this->capacity;
        }

        return $this->capacity + intdiv($this->overflow, 2);
    }

    public function overBase(): int
    {
        return max(0, $this->used - $this->capacity);
    }

    public function overHard(): int
    {
        return max(0, $this->used - $this->hardLimit());
    }

    public function overRecommended(): int
    {
        return max(0, $this->used - $this->recommendedLimit());
    }

    /**
     * @return 'ok'|'overflow'|'critical'
     */
    public function zone(): string
    {
        if ($this->used > $this->hardLimit()) {
            return 'critical';
        }

        if ($this->used > $this->capacity) {
            return 'overflow';
        }

        return 'ok';
    }

    public function isOverBase(): bool
    {
        return $this->zone() !== 'ok';
    }

    public function isCritical(): bool
    {
        return $this->zone() === 'critical';
    }

    public function isOverflow(): bool
    {
        return $this->zone() === 'overflow';
    }

    /**
     * Scale for the meter: hard limit when overflow is configured, else base capacity.
     */
    public function scaleMax(): int
    {
        return max(1, $this->hardLimit() > 0 ? $this->hardLimit() : max(1, $this->capacity));
    }

    public function fillPercent(): float
    {
        return min(100.0, 100.0 * $this->used / $this->scaleMax());
    }

    public function capacityMarkerPercent(): float
    {
        if ($this->overflow <= 0) {
            return 100.0;
        }

        return min(100.0, 100.0 * $this->capacity / $this->scaleMax());
    }

    public function recommendedMarkerPercent(): float
    {
        if ($this->overflow <= 0) {
            return 100.0;
        }

        return min(100.0, 100.0 * $this->recommendedLimit() / $this->scaleMax());
    }

    public function badgeColor(): string
    {
        return match ($this->zone()) {
            'critical' => 'red',
            'overflow' => 'orange',
            default => 'zinc',
        };
    }

    public function barColorClass(string $okClass): string
    {
        return match ($this->zone()) {
            'critical' => 'bg-red-500',
            'overflow' => 'bg-orange-500',
            default => $okClass,
        };
    }

    public function statusLabel(): ?string
    {
        return match ($this->zone()) {
            'critical' => $this->overflow > 0
                ? 'Over limit · +'.$this->overHard().' past max '.$this->hardLimit()
                : 'Over capacity · +'.$this->overBase(),
            'overflow' => $this->overRecommended() > 0
                ? 'Past aim · +'.$this->overBase().' double parked'
                : 'Double parking · +'.$this->overBase().' of '.$this->overflow,
            default => null,
        };
    }

    public function shortStatusLabel(): ?string
    {
        return match ($this->zone()) {
            'critical' => $this->overflow > 0
                ? 'Over limit +'.$this->overHard()
                : 'Over limit +'.$this->overBase(),
            'overflow' => $this->overRecommended() > 0
                ? 'Past aim +'.$this->overBase()
                : 'Double park +'.$this->overBase(),
            default => null,
        };
    }

    public function statusTextClass(): string
    {
        return match ($this->zone()) {
            'critical' => 'text-red-700 dark:text-red-300',
            'overflow' => 'text-orange-700 dark:text-orange-300',
            default => 'text-zinc-500 dark:text-zinc-400',
        };
    }

    public function statusChipClass(): string
    {
        return match ($this->zone()) {
            'critical' => 'bg-red-100 text-red-800 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900',
            'overflow' => 'bg-orange-100 text-orange-800 ring-orange-200 dark:bg-orange-950 dark:text-orange-200 dark:ring-orange-900',
            default => '',
        };
    }

    public function tooltipExtra(): string
    {
        if ($this->overflow <= 0) {
            return '';
        }

        return ' · base '.$this->capacity
            .' · aim '.$this->recommendedLimit()
            .' · max '.$this->hardLimit();
    }
}
