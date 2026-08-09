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
            'drop_off_coaches' => $this->dropOffCoachesSubquery(),
        ];

        foreach (ConventionDay::singleDayKeys() as $day) {
            $selects['assigned_'.strtolower($day)] = $this->assignedForDaySubquery($day);
            $selects['drop_off_'.strtolower($day)] = $this->dropOffCoachesForDaySubquery($day);
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
                ->where(fn ($q) => $this->scopeExcludingDropOffCoaches($q))
                ->count();
        }

        return $counts;
    }

    /**
     * Coaches marked as not staying on site (drop-off only) for a park.
     *
     * @return array{friday: int, saturday: int, sunday: int, total: int}
     */
    public function dropOffCoachCountsForPark(int $carParkId): array
    {
        $counts = ['total' => 0];

        foreach (ConventionDay::singleDayKeys() as $day) {
            $counts[strtolower($day)] = ParkingRegistration::query()
                ->assignedToCarPark($carParkId)
                ->whereJsonContains('days', $day)
                ->where(fn ($q) => $this->scopeOnlyDropOffCoaches($q))
                ->count();
        }

        $counts['total'] = ParkingRegistration::query()
            ->assignedToCarPark($carParkId)
            ->where(fn ($q) => $this->scopeOnlyDropOffCoaches($q))
            ->count();

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
            ->where(fn ($q) => $this->scopeExcludingDropOffCoaches($q, 'parking_registrations'))
            ->where(function ($q) {
                $q->whereColumn('parking_registrations.car_park_id', 'car_parks.id')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('parking_registrations.car_park_id')
                            ->whereColumn('congregations.car_park_id', 'car_parks.id');
                    });
            });
    }

    protected function dropOffCoachesSubquery(): Builder
    {
        return ParkingRegistration::query()->selectRaw('count(*)')
            ->leftJoin('congregations', fn ($join) => $join->whereRaw('TRIM(congregations.name) = TRIM(parking_registrations.congregation)'))
            ->where(fn ($q) => $this->scopeOnlyDropOffCoaches($q, 'parking_registrations'))
            ->where(function ($q) {
                $q->whereColumn('parking_registrations.car_park_id', 'car_parks.id')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('parking_registrations.car_park_id')
                            ->whereColumn('congregations.car_park_id', 'car_parks.id');
                    });
            });
    }

    protected function dropOffCoachesForDaySubquery(string $day): Builder
    {
        return ParkingRegistration::query()->selectRaw('count(*)')
            ->leftJoin('congregations', fn ($join) => $join->whereRaw('TRIM(congregations.name) = TRIM(parking_registrations.congregation)'))
            ->whereJsonContains('parking_registrations.days', $day)
            ->where(fn ($q) => $this->scopeOnlyDropOffCoaches($q, 'parking_registrations'))
            ->where(function ($q) {
                $q->whereColumn('parking_registrations.car_park_id', 'car_parks.id')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('parking_registrations.car_park_id')
                            ->whereColumn('congregations.car_park_id', 'car_parks.id');
                    });
            });
    }

    /**
     * Space-taking registrations: everything except coaches marked not staying on site.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\ParkingRegistration>  $query
     */
    protected function scopeExcludingDropOffCoaches($query, string $table = 'parking_registrations')
    {
        return $query->whereNot(function ($q) use ($table): void {
            $q->where($table.'.vehicle_type', 'coach')
                ->where($table.'.coach_staying_on_site', false);
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\ParkingRegistration>  $query
     */
    protected function scopeOnlyDropOffCoaches($query, string $table = 'parking_registrations')
    {
        return $query
            ->where($table.'.vehicle_type', 'coach')
            ->where($table.'.coach_staying_on_site', false);
    }
}
