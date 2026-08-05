<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParkingExtra extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIONED = 'actioned';

    protected $fillable = [
        'name',
        'congregation',
        'contact_number',
        'email',
        'vehicle_type',
        'vehicle_registration',
        'days',
        'elderly_infirm_parking',
        'notes',
        'status',
        'parking_registration_id',
        'actioned_at',
        'actioned_by',
    ];

    protected $casts = [
        'days' => 'array',
        'elderly_infirm_parking' => 'boolean',
        'actioned_at' => 'datetime',
    ];

    public function parkingRegistration(): BelongsTo
    {
        return $this->belongsTo(ParkingRegistration::class);
    }

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActioned(): bool
    {
        return $this->status === self::STATUS_ACTIONED;
    }
}
