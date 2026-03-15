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
        'car_park_id',
        'contact_number',
        'vehicle_registration',
        'days',
        'email',
        'vehicle_type',
        'elderly_infirm_parking',
    ];

    protected $casts = [
        'days' => 'array',
        'elderly_infirm_parking' => 'boolean',
    ];

    public function carPark(): BelongsTo
    {
        return $this->belongsTo(CarPark::class);
    }
}
