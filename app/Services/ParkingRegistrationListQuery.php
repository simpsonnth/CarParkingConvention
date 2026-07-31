<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ParkingRegistration;
use App\Support\ConventionDay;
use App\Support\ParkingRegistrationListFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ParkingRegistrationListQuery
{
    public const CIRCUIT_OVERSEER_CONGREGATION_LABELS = [
        'Circuit Overseer',
        'Circuit Overseers',
    ];

    /**
     * @param  Builder<ParkingRegistration>  $query
     * @return Builder<ParkingRegistration>
     */
    public function apply(Builder $query, ParkingRegistrationListFilters $filters): Builder
    {
        $query
            ->when($filters->search !== '', function ($q) use ($filters): void {
                $q->where(function ($q2) use ($filters): void {
                    $q2->where('name', 'like', '%'.$filters->search.'%')
                        ->orWhere('vehicle_registration', 'like', '%'.$filters->search.'%')
                        ->orWhere('congregation', 'like', '%'.$filters->search.'%')
                        ->orWhere('email', 'like', '%'.$filters->search.'%');
                });
            })
            ->when($filters->congregations !== [], function ($q) use ($filters): void {
                $this->applyCongregationFilter($q, $filters->congregations);
            })
            ->when($filters->carParkIds !== [], function ($q) use ($filters): void {
                $q->assignedToAnyCarPark($filters->carParkIds);
            })
            ->when($filters->unassignedCarPark, function ($q): void {
                $q->withoutEffectiveCarPark();
            })
            ->when($filters->vehicleTypes !== [], function ($q) use ($filters): void {
                $q->whereIn('vehicle_type', $filters->vehicleTypes);
            })
            ->when($filters->days !== [], function ($q) use ($filters): void {
                $this->applyExactDaysFilter($q, $filters->days);
            })
            ->when($filters->elderlyInfirm !== null, function ($q) use ($filters): void {
                $q->where('elderly_infirm_parking', $filters->elderlyInfirm);
            })
            ->when($filters->ticketSent !== null, function ($q) use ($filters): void {
                if ($filters->ticketSent) {
                    $q->whereNotNull('ticket_sent_at');
                } else {
                    $q->whereNull('ticket_sent_at');
                }
            })
            ->when($filters->duplicatesOnly, function ($q): void {
                $signals = app(ParkingRegistrationDuplicateSignals::class);
                $dupEmails = array_keys($signals->duplicateNormalizedEmailKeys());
                $dupRegs = array_keys($signals->duplicateNormalizedVehicleRegKeys());

                if ($dupEmails === [] && $dupRegs === []) {
                    $q->whereRaw('1 = 0');

                    return;
                }

                $q->where(function ($q2) use ($dupEmails, $dupRegs): void {
                    if ($dupEmails !== [] && $dupRegs !== []) {
                        $q2->whereIn(DB::raw('LOWER(TRIM(email))'), $dupEmails)
                            ->orWhereIn('vehicle_registration', $dupRegs);
                    } elseif ($dupEmails !== []) {
                        $q2->whereIn(DB::raw('LOWER(TRIM(email))'), $dupEmails);
                    } else {
                        $q2->whereIn('vehicle_registration', $dupRegs);
                    }
                });
            });

        $sortColumn = match ($filters->sortBy) {
            'name' => 'name',
            'congregation' => 'congregation',
            'created_at' => 'created_at',
            'vehicle_registration' => 'vehicle_registration',
            default => 'created_at',
        };
        $query->orderBy($sortColumn, $filters->sortDir === 'desc' ? 'desc' : 'asc');

        return $query;
    }

    /**
     * Match registrations whose `days` JSON set equals the selected days (order-independent).
     *
     * @param  Builder<ParkingRegistration>  $query
     * @param  list<string>  $days
     */
    protected function applyExactDaysFilter(Builder $query, array $days): void
    {
        $normalized = ParkingRegistrationListFilters::normalizeDays($days);
        if ($normalized === []) {
            return;
        }

        foreach ($normalized as $day) {
            if (! in_array($day, ConventionDay::singleDayKeys(), true)) {
                continue;
            }
            $query->whereJsonContains('days', $day);
        }

        $query->whereJsonLength('days', count($normalized));
    }

    /**
     * @param  Builder<ParkingRegistration>  $query
     * @param  list<string>  $filterCongregations
     */
    protected function applyCongregationFilter(Builder $query, array $filterCongregations): void
    {
        $selectedCircuitOverseer = array_values(array_intersect(
            $filterCongregations,
            self::CIRCUIT_OVERSEER_CONGREGATION_LABELS
        ));
        $selectedCongregations = array_values(array_diff(
            $filterCongregations,
            self::CIRCUIT_OVERSEER_CONGREGATION_LABELS
        ));

        $query->where(function ($outer) use ($selectedCongregations, $selectedCircuitOverseer): void {
            if ($selectedCongregations !== []) {
                $outer->whereIn('congregation', $selectedCongregations);
            }

            if ($selectedCircuitOverseer !== []) {
                if ($selectedCongregations !== []) {
                    $outer->orWhere('is_circuit_overseer', true);
                } else {
                    $outer->where('is_circuit_overseer', true);
                }
            }
        });
    }
}
