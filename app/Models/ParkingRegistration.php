<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParkingRegistration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'congregation',
        'is_circuit_overseer',
        'car_park_id',
        'contact_number',
        'vehicle_registration',
        'days',
        'email',
        'vehicle_type',
        'sharing_with_other_congregations',
        'sharing_congregations_notes',
        'elderly_infirm_parking',
        'coach_captain_to_be_assigned',
        'coach_staying_on_site',
    ];

    protected $casts = [
        'days' => 'array',
        'is_circuit_overseer' => 'boolean',
        'elderly_infirm_parking' => 'boolean',
        'sharing_with_other_congregations' => 'boolean',
        'coach_captain_to_be_assigned' => 'boolean',
        'coach_staying_on_site' => 'boolean',
    ];

    public function carPark(): BelongsTo
    {
        return $this->belongsTo(CarPark::class);
    }

    /** Scope: registrations counted as assigned to this car park (individual override or congregation default). */
    public function scopeAssignedToCarPark($query, int $carParkId)
    {
        return $query
            ->leftJoin('congregations', fn ($join) => $join->whereRaw('TRIM(congregations.name) = TRIM(parking_registrations.congregation)'))
            ->where(function ($q) use ($carParkId) {
                $q->where('parking_registrations.car_park_id', $carParkId)
                    ->orWhere(function ($q2) use ($carParkId) {
                        $q2->whereNull('parking_registrations.car_park_id')
                            ->where('congregations.car_park_id', $carParkId);
                    });
            });
    }
}
