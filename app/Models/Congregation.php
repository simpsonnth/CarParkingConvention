<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Congregation extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'car_park_id', 'uuid'];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function carPark(): BelongsTo
    {
        return $this->belongsTo(CarPark::class);
    }

    public function parkingPasses(): HasMany
    {
        return $this->hasMany(ParkingPass::class);
    }

    public function numbersResponse(): HasOne
    {
        return $this->hasOne(CongregationNumbersResponse::class);
    }
}
