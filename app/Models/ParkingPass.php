<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingPass extends Model
{
    protected $fillable = [
        'congregation_id',
        'car_park_id',
        'status',
        'vehicle_reg',
        'contact_number',
        'scanned_at',
        'left_at',
        'scanned_by_user_id',
        'check_in_latitude',
        'check_in_longitude',
        'name',
        'email',
        'days',
        'elderly_infirm_parking',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'left_at' => 'datetime',
            'days' => 'array',
            'elderly_infirm_parking' => 'boolean',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
        ];
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function carPark(): BelongsTo
    {
        return $this->belongsTo(CarPark::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }

    public function hasCheckInLocation(): bool
    {
        return $this->check_in_latitude !== null && $this->check_in_longitude !== null;
    }

    public function checkInNavigationUrl(): ?string
    {
        if (! $this->hasCheckInLocation()) {
            return null;
        }

        return sprintf(
            'https://www.google.com/maps/dir/?api=1&destination=%s,%s',
            rtrim(rtrim(number_format((float) $this->check_in_latitude, 7, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format((float) $this->check_in_longitude, 7, '.', ''), '0'), '.')
        );
    }

    /** Scope: actively parked today (ignores stale multi-day leftovers). */
    public function scopeParkedToday($query)
    {
        return $query->where('status', 'parked')
            ->whereDate('scanned_at', today());
    }

    /** Scope: passes counted as parked at this car park (own car_park_id or legacy congregation assignment). */
    public function scopeParkedAtCarPark($query, int $carParkId)
    {
        return $query->where('status', 'parked')
            ->where(function ($q) use ($carParkId) {
                $q->where('car_park_id', $carParkId)
                    ->orWhere(function ($q2) use ($carParkId) {
                        $q2->whereNull('car_park_id')
                            ->whereHas('congregation', fn ($c) => $c->where('car_park_id', $carParkId));
                    });
            });
    }

    public function scopeWithNormalizedVehicleReg($query, string $formattedReg)
    {
        return $query->whereRaw('REPLACE(UPPER(COALESCE(vehicle_reg, \'\')), \' \', \'\') = ?', [$formattedReg]);
    }
}
