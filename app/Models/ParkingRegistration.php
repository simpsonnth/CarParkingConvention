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
    ];

    protected $casts = [
        'days' => 'array',
        'is_circuit_overseer' => 'boolean',
        'elderly_infirm_parking' => 'boolean',
        'sharing_with_other_congregations' => 'boolean',
        'coach_captain_to_be_assigned' => 'boolean',
    ];

    public function carPark(): BelongsTo
    {
        return $this->belongsTo(CarPark::class);
    }
}
