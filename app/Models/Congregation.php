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

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Resolve route segments: standard UUID strings, legacy numeric ids, and
     * digit-only uuid column values (e.g. "5252") which must not be treated as primary keys.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        $value = (string) $value;

        if (ctype_digit($value)) {
            $byId = static::query()->where('id', (int) $value)->first();
            if ($byId !== null) {
                return $byId;
            }
        }

        return static::query()->where('uuid', $value)->firstOrFail();
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
