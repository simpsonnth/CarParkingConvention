<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        'name',
        'email',
        'days',
        'elderly_infirm_parking',
        'notes',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'left_at' => 'datetime',
        'days' => 'array',
        'elderly_infirm_parking' => 'boolean',
    ];

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
}