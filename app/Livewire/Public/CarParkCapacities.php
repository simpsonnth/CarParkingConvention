<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Models\CarPark;
use App\Services\CarParkDayCapacityMetrics;
use App\Support\CarParkCapacityReading;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public-fullscreen')]
class CarParkCapacities extends Component
{
    public function render(CarParkDayCapacityMetrics $dayCapacityMetrics)
    {
        $showExpected = auth()->check();

        $parks = CarPark::query()
            ->orderBy('name')
            ->addSelect($dayCapacityMetrics->listSelectSubqueries())
            ->get()
            ->map(function (CarPark $park) use ($showExpected): array {
                $overflow = $park->overflowCapacity();
                $liveCapacity = $park->capacityForToday();
                $parked = (int) $park->current_occupancy;
                $liveReading = CarParkCapacityReading::make($parked, $liveCapacity, $overflow);

                $days = [
                    [
                        'key' => 'live',
                        'label' => 'Live',
                        'mode' => 'live',
                        'reading' => $liveReading,
                        'drop_off' => 0,
                        'tooltip' => "{$parked} clocked in / {$liveCapacity} today".$liveReading->tooltipExtra(),
                    ],
                ];

                if ($showExpected) {
                    foreach ([
                        'friday' => [(int) $park->assigned_friday, (int) $park->capacity_friday, (int) ($park->drop_off_friday ?? 0)],
                        'saturday' => [(int) $park->assigned_saturday, (int) $park->capacity_saturday, (int) ($park->drop_off_saturday ?? 0)],
                        'sunday' => [(int) $park->assigned_sunday, (int) $park->capacity_sunday, (int) ($park->drop_off_sunday ?? 0)],
                    ] as $key => [$assigned, $capacity, $dropOff]) {
                        $reading = CarParkCapacityReading::make($assigned, $capacity, $overflow);
                        $days[] = [
                            'key' => $key,
                            'label' => ucfirst($key),
                            'mode' => 'day',
                            'reading' => $reading,
                            'drop_off' => $dropOff,
                            'tooltip' => "{$assigned} registered / {$capacity} capacity"
                                .$reading->tooltipExtra()
                                .($dropOff > 0 ? " · {$dropOff} drop-off" : ''),
                        ];
                    }
                }

                $worst = collect($days)
                    ->map(fn (array $day): string => $day['reading']->zone())
                    ->contains('critical')
                    ? 'critical'
                    : (collect($days)->map(fn (array $day): string => $day['reading']->zone())->contains('overflow')
                        ? 'overflow'
                        : 'ok');

                return [
                    'park' => $park,
                    'overflow' => $overflow,
                    'days' => $days,
                    'worst' => $worst,
                    'drop_off_total' => (int) ($park->drop_off_coaches ?? 0),
                ];
            });

        return view('livewire.public.car-park-capacities', [
            'parkCards' => $parks,
            'showExpected' => $showExpected,
        ]);
    }
}
