<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelGuestParkingRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const CONGREGATION_NAME = 'Radisson Hotel Guest';

    /** @var list<string> */
    public const ALLOWED_DAYS = [
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    protected $fillable = [
        'name',
        'contact_number',
        'vehicle_registration',
        'email',
        'days',
        'status',
        'car_park_id',
        'parking_registration_id',
        'admin_notes',
        'actioned_at',
        'actioned_by',
    ];

    protected $casts = [
        'days' => 'array',
        'actioned_at' => 'datetime',
    ];

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function carPark(): BelongsTo
    {
        return $this->belongsTo(CarPark::class);
    }

    public function parkingRegistration(): BelongsTo
    {
        return $this->belongsTo(ParkingRegistration::class)->withTrashed();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
