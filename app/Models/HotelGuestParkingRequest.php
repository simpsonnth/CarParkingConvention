<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ConventionDay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelGuestParkingRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const CONGREGATION_NAME = 'Radisson Hotel Guest';

    /** Public congregation code guests enter on ticket-change-request. */
    public const PUBLIC_CODE = 'RADISSON';

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

    /**
     * Ensure the Radisson Hotel Guest congregation exists with the public RADISSON code.
     */
    public static function ensureCongregation(?int $defaultCarParkId = null): Congregation
    {
        $byCode = Congregation::query()
            ->whereRaw('LOWER(TRIM(uuid)) = ?', [mb_strtolower(self::PUBLIC_CODE)])
            ->first();

        if ($byCode !== null) {
            if (trim((string) $byCode->name) !== self::CONGREGATION_NAME) {
                $byCode->update(['name' => self::CONGREGATION_NAME]);
            }
            if ($defaultCarParkId !== null && $byCode->car_park_id === null) {
                $byCode->update(['car_park_id' => $defaultCarParkId]);
            }

            return $byCode->fresh() ?? $byCode;
        }

        $byName = Congregation::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(self::CONGREGATION_NAME)])
            ->first();

        if ($byName !== null) {
            $byName->update([
                'uuid' => self::PUBLIC_CODE,
                'name' => self::CONGREGATION_NAME,
                'car_park_id' => $byName->car_park_id ?? $defaultCarParkId,
            ]);

            return $byName->fresh() ?? $byName;
        }

        return Congregation::query()->create([
            'name' => self::CONGREGATION_NAME,
            'uuid' => self::PUBLIC_CODE,
            'car_park_id' => $defaultCarParkId,
        ]);
    }

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

    /**
     * Hotel nights stay on the ticket, and Fri/Sat/Sun are always included because
     * Radisson guests are assumed to attend all three convention days.
     *
     * @param  list<string>  $hotelNights
     * @return list<string>
     */
    public static function registrationDaysForHotelStay(array $hotelNights): array
    {
        $merged = array_unique([
            ...array_intersect(self::ALLOWED_DAYS, $hotelNights),
            ...ConventionDay::singleDayKeys(),
        ]);

        return array_values(array_intersect(self::ALLOWED_DAYS, $merged));
    }
}
