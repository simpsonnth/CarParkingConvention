<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use App\Support\ConventionDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CarParkDayCapacityMetrics
{
    /**
     * Subselect aliases for CarPark::addSelect — parked occupancy + assigned demand per day.
     *
     * @return array<string, Builder|QueryBuilder>
     */
    public function listSelectSubqueries(): array
    {
        $selects = [
            'current_occupancy' => $this->parkedOccupancySubquery(),
        ];

        foreach (ConventionDay::singleDayKeys() as $day) {
            $selects['assigned_'.strtolower($day)] = $this->assignedForDaySubquery($day);
        }

        return $selects;
    }

    /**
     * @return array{friday: int, saturday: int, sunday: int}
     */
    public function assignedCountsForPark(int $carParkId): array
    {
        $counts = [];

        foreach (ConventionDay::singleDayKeys() as $day) {
            $counts[strtolower($day)] = ParkingRegistration::query()
                ->assignedToCarPark($carParkId)
                ->whereJsonContains('days', $day)
                ->count();
        }

        return $counts;
    }

    protected function parkedOccupancySubquery(): Builder
    {
        return ParkingPass::query()->selectRaw('count(*)')
            ->leftJoin('congregations', 'congregations.id', '=', 'parking_passes.congregation_id')
            ->where('parking_passes.status', 'parked')
            ->where(function ($q) {
                $q->whereColumn('parking_passes.car_park_id', 'car_parks.id')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('parking_passes.car_park_id')
                            ->whereColumn('congregations.car_park_id', 'car_parks.id');
                    });
            });
    }

    protected function assignedForDaySubquery(string $day): Builder
    {
        return ParkingRegistration::query()->selectRaw('count(*)')
            ->leftJoin('congregations', fn ($join) => $join->whereRaw('TRIM(congregations.name) = TRIM(parking_registrations.congregation)'))
            ->whereJsonContains('parking_registrations.days', $day)
            ->where(function ($q) {
                $q->whereColumn('parking_registrations.car_park_id', 'car_parks.id')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('parking_registrations.car_park_id')
                            ->whereColumn('congregations.car_park_id', 'car_parks.id');
                    });
            });
    }
}
