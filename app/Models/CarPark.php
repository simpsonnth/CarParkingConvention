<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarPark extends Model
{
    protected $fillable = ['name', 'capacity', 'location', 'color'];

    public function congregations(): HasMany
    {
        return $this->hasMany(Congregation::class);
    }

    public function parkingPasses(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(ParkingPass::class, Congregation::class);
    }
}
