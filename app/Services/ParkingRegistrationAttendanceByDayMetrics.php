<?php

namespace App\Services;

use App\Models\ParkingRegistration;
use Illuminate\Database\Eloquent\Builder;

final class ParkingRegistrationAttendanceByDayMetrics
{
    /** @var list<string> */
    private const ORDERED_DAYS = ['Friday', 'Saturday', 'Sunday'];

    /**
     * Per-day counts are registrations whose `days` JSON array contains that convention day.
     * A registration attending multiple days increments each corresponding day bucket (parking demand semantics).
     * Sum of day counts may exceed total registration rows.
     *
     * `counts_by_day` is total vehicles attending that day (one registration = one vehicle).
     * `cars_by_day` + `coaches_by_day` equals `counts_by_day` (non-coach rows count as cars).
     * `circuit_overseers_by_day` counts registrations with `is_circuit_overseer` (subset of totals).
     *
     * @return array{
     *     ordered_days: list<string>,
     *     counts_by_day: array<string, int>,
     *     cars_by_day: array<string, int>,
     *     coaches_by_day: array<string, int>,
     *     circuit_overseers_by_day: array<string, int>,
     *     disabled_by_day: array<string, int>,
     *     total_registrations: int,
     *     missing_days_count: int,
     * }
     */
    public function compute(): array
    {
        $countsByDay = [];
        $carsByDay = [];
        $coachesByDay = [];
        $circuitOverseersByDay = [];
        $disabledByDay = [];

        foreach (self::ORDERED_DAYS as $day) {
            $countsByDay[$day] = $this->countForConventionDay($day);
            $carsByDay[$day] = $this->countForConventionDay($day, function (Builder $q): void {
                $q->where(function (Builder $inner): void {
                    $inner->whereNull('vehicle_type')->orWhere('vehicle_type', '<>', 'coach');
                });
            });
            $coachesByDay[$day] = $this->countForConventionDay($day, function (Builder $q): void {
                $q->where('vehicle_type', 'coach');
            });
            $circuitOverseersByDay[$day] = $this->countForConventionDay($day, function (Builder $q): void {
                $q->where('is_circuit_overseer', true);
            });
            $disabledByDay[$day] = $this->countForConventionDay($day, function (Builder $q): void {
                $q->where('elderly_infirm_parking', true);
            });
        }

        $totalRegistrations = (int) ParkingRegistration::query()->count();

        $missingDaysCount = (int) ParkingRegistration::query()
            ->where(function ($q): void {
                $q->whereNull('days')
                    ->orWhereJsonLength('days', '=', 0);
            })
            ->count();

        return [
            'ordered_days' => self::ORDERED_DAYS,
            'counts_by_day' => $countsByDay,
            'cars_by_day' => $carsByDay,
            'coaches_by_day' => $coachesByDay,
            'circuit_overseers_by_day' => $circuitOverseersByDay,
            'disabled_by_day' => $disabledByDay,
            'total_registrations' => $totalRegistrations,
            'missing_days_count' => $missingDaysCount,
        ];
    }

    private function countForConventionDay(string $day, ?callable $scope = null): int
    {
        $query = ParkingRegistration::query()->whereJsonContains('days', $day);

        if ($scope !== null) {
            $scope($query);
        }

        return (int) $query->count();
    }
}
