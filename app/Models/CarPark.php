<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ConventionDay;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CarPark extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'capacity_friday',
        'capacity_saturday',
        'capacity_sunday',
        'overflow_capacity',
        'location',
        'latitude',
        'longitude',
        'postcode',
        'map_image_path',
        'travel_directions',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'overflow_capacity' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CarPark $carPark): void {
            $carPark->syncDayCapacitiesFromLegacy();
            $carPark->syncLegacyCapacityFromDays();
        });
    }

    public function mapImageUrl(): ?string
    {
        return $this->map_image_path ?: null;
    }

    public function hasNavigation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function navigationUrl(): ?string
    {
        if (! $this->hasNavigation()) {
            return null;
        }

        return sprintf(
            'https://www.google.com/maps/dir/?api=1&destination=%s,%s',
            rtrim(rtrim(number_format((float) $this->latitude, 7, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format((float) $this->longitude, 7, '.', ''), '0'), '.')
        );
    }

    public function congregations(): HasMany
    {
        return $this->hasMany(Congregation::class);
    }

    public function parkingPasses(): HasManyThrough
    {
        return $this->hasManyThrough(ParkingPass::class, Congregation::class);
    }

    public function capacityForDay(string $day): int
    {
        return match ($day) {
            ConventionDay::FRIDAY => (int) ($this->capacity_friday ?? $this->capacity),
            ConventionDay::SATURDAY => (int) ($this->capacity_saturday ?? $this->capacity),
            ConventionDay::SUNDAY => (int) ($this->capacity_sunday ?? $this->capacity),
            default => (int) $this->capacity,
        };
    }

    public function capacityForToday(?CarbonInterface $now = null): int
    {
        $now ??= now();

        $day = match ($now->dayOfWeek) {
            CarbonInterface::FRIDAY => ConventionDay::FRIDAY,
            CarbonInterface::SATURDAY => ConventionDay::SATURDAY,
            CarbonInterface::SUNDAY => ConventionDay::SUNDAY,
            default => null,
        };

        return $day === null ? (int) $this->capacity : $this->capacityForDay($day);
    }

    public function overflowCapacity(): int
    {
        return max(0, (int) ($this->overflow_capacity ?? 0));
    }

    public function hardLimitForDay(string $day): int
    {
        return $this->capacityForDay($day) + $this->overflowCapacity();
    }

    public function hardLimitForToday(?CarbonInterface $now = null): int
    {
        return $this->capacityForToday($now) + $this->overflowCapacity();
    }

    public function contrastingTextColor(): string
    {
        $hex = ltrim((string) ($this->color ?? ''), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#ffffff';
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        return $luminance > 0.5 ? '#18181b' : '#ffffff';
    }

    protected function syncDayCapacitiesFromLegacy(): void
    {
        if ($this->capacity === null || $this->capacity === '') {
            return;
        }

        $legacy = (int) $this->capacity;

        if ($this->capacity_friday === null) {
            $this->capacity_friday = $legacy;
        }

        if ($this->capacity_saturday === null) {
            $this->capacity_saturday = $legacy;
        }

        if ($this->capacity_sunday === null) {
            $this->capacity_sunday = $legacy;
        }
    }

    protected function syncLegacyCapacityFromDays(): void
    {
        $friday = $this->capacity_friday;
        $saturday = $this->capacity_saturday;
        $sunday = $this->capacity_sunday;

        if ($friday === null && $saturday === null && $sunday === null) {
            return;
        }

        $this->capacity = max(
            (int) ($friday ?? 0),
            (int) ($saturday ?? 0),
            (int) ($sunday ?? 0),
        );
    }
}
