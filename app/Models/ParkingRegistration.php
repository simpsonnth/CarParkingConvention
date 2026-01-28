<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingRegistration extends Model
{
    protected $fillable = [
        'name',
        'congregation',
        'contact_number',
        'vehicle_registration',
        'days',
        'email',
    ];

    protected $casts = [
        'days' => 'array',
    ];
}
