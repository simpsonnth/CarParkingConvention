<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingIncidentReport extends Model
{
    public const TYPE_NEAR_MISS = 'near_miss';

    public const TYPE_ACCIDENT = 'accident';

    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    /** @return list<string> */
    public static function typeKeys(): array
    {
        return [self::TYPE_NEAR_MISS, self::TYPE_ACCIDENT];
    }

    /** @return list<string> */
    public static function severityKeys(): array
    {
        return [self::SEVERITY_LOW, self::SEVERITY_MEDIUM, self::SEVERITY_HIGH];
    }

    protected $fillable = [
        'type',
        'occurred_at',
        'location',
        'car_park_id',
        'description',
        'actions_taken',
        'injury_reported',
        'severity',
        'reporter_name',
        'reporter_email',
        'reporter_phone',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'injury_reported' => 'boolean',
    ];

    public function carPark(): BelongsTo
    {
        return $this->belongsTo(CarPark::class);
    }

    public function requiresSeverity(): bool
    {
        return $this->injury_reported || $this->type === self::TYPE_ACCIDENT;
    }
}
