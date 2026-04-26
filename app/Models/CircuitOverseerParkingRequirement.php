<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CircuitOverseerParkingRequirement extends Model
{
    protected $fillable = [
        'first_name',
        'car_park_tickets_count',
        'disabled_parking_required',
    ];

    protected function casts(): array
    {
        return [
            'car_park_tickets_count' => 'integer',
            'disabled_parking_required' => 'boolean',
        ];
    }
}
