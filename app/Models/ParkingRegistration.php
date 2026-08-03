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
        'ticket_sent_at',
    ];

    protected $casts = [
        'days' => 'array',
        'is_circuit_overseer' => 'boolean',
        'elderly_infirm_parking' => 'boolean',
        'sharing_with_other_congregations' => 'boolean',
        'coach_captain_to_be_assigned' => 'boolean',
        'coach_staying_on_site' => 'boolean',
        'ticket_sent_at' => 'datetime',
    ];

    public function carPark(): BelongsTo
    {
        return $this->belongsTo(CarPark::class);
    }

    /**
     * Ticket number printed on master passes (zero-padded registration id).
     */
    public function ticketNumber(): string
    {
        return str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /** Scope: registrations counted as assigned to this car park (individual override or congregation default). */
    public function scopeAssignedToCarPark($query, int $carParkId)
    {
        return $query->assignedToAnyCarPark([$carParkId]);
    }

    /**
     * @param  list<int>  $carParkIds
     */
    public function scopeAssignedToAnyCarPark($query, array $carParkIds)
    {
        $carParkIds = array_values(array_filter(array_map('intval', $carParkIds)));

        if ($carParkIds === []) {
            return $query;
        }

        return $query
            ->leftJoin('congregations', fn ($join) => $join->whereRaw('TRIM(congregations.name) = TRIM(parking_registrations.congregation)'))
            ->where(function ($q) use ($carParkIds): void {
                foreach ($carParkIds as $carParkId) {
                    $q->orWhere(function ($parkQuery) use ($carParkId): void {
                        $parkQuery->where('parking_registrations.car_park_id', $carParkId)
                            ->orWhere(function ($inheritedQuery) use ($carParkId): void {
                                $inheritedQuery->whereNull('parking_registrations.car_park_id')
                                    ->where('congregations.car_park_id', $carParkId);
                            });
                    });
                }
            })
            ->select('parking_registrations.*');
    }

    /** Registrations with no individual override and no congregation default car park. */
    public function scopeWithoutEffectiveCarPark($query)
    {
        return $query
            ->leftJoin('congregations', fn ($join) => $join->whereRaw('TRIM(congregations.name) = TRIM(parking_registrations.congregation)'))
            ->whereNull('parking_registrations.car_park_id')
            ->whereNull('congregations.car_park_id')
            ->select('parking_registrations.*');
    }
}
