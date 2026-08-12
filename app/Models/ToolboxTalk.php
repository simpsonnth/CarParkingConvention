<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ToolboxTalk extends Model
{
    public const SCOPE_CORE = 'core';

    public const SCOPE_PARK = 'park';

    protected $fillable = [
        'talk_date',
        'scope',
        'car_park_id',
        'deck_key',
    ];

    protected function casts(): array
    {
        return [
            'talk_date' => 'date',
        ];
    }

    /** @return list<string> */
    public static function scopeKeys(): array
    {
        return [self::SCOPE_CORE, self::SCOPE_PARK];
    }

    public static function deckKeyForCore(): string
    {
        return self::SCOPE_CORE;
    }

    public static function deckKeyForPark(int $carParkId): string
    {
        return self::SCOPE_PARK.'-'.$carParkId;
    }

    public static function findCoreForDate(Carbon|string $date): ?self
    {
        return self::query()
            ->whereDate('talk_date', $date)
            ->where('deck_key', self::deckKeyForCore())
            ->first();
    }

    public static function findParkForDate(Carbon|string $date, int $carParkId): ?self
    {
        return self::query()
            ->whereDate('talk_date', $date)
            ->where('deck_key', self::deckKeyForPark($carParkId))
            ->first();
    }

    public static function firstOrCreateCore(Carbon|string $date): self
    {
        $dateString = Carbon::parse($date)->toDateString();
        $existing = self::findCoreForDate($dateString);
        if ($existing !== null) {
            return $existing;
        }

        return self::query()->create([
            'talk_date' => $dateString,
            'scope' => self::SCOPE_CORE,
            'car_park_id' => null,
            'deck_key' => self::deckKeyForCore(),
        ]);
    }

    public static function firstOrCreatePark(Carbon|string $date, int $carParkId): self
    {
        $dateString = Carbon::parse($date)->toDateString();
        $existing = self::findParkForDate($dateString, $carParkId);
        if ($existing !== null) {
            return $existing;
        }

        return self::query()->create([
            'talk_date' => $dateString,
            'scope' => self::SCOPE_PARK,
            'car_park_id' => $carParkId,
            'deck_key' => self::deckKeyForPark($carParkId),
        ]);
    }

    public function carPark(): BelongsTo
    {
        return $this->belongsTo(CarPark::class);
    }

    public function slides(): HasMany
    {
        return $this->hasMany(ToolboxTalkSlide::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isCore(): bool
    {
        return $this->scope === self::SCOPE_CORE;
    }
}
