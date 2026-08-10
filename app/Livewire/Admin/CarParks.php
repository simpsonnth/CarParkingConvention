<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\CarParks\CarParkMapImageStorage;
use App\Models\CarPark;
use App\Services\CarParkDayCapacityMetrics;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CarParks extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $name = '';

    public string $capacityFriday = '';

    public string $capacitySaturday = '';

    public string $capacitySunday = '';

    public string $overflowCapacity = '0';

    public string $location = '';

    public string $color = '';

    public string $travelDirections = '';

    public ?int $carParkId = null;

    public $mapImage = null;

    public string $existingMapImage = '';

    public bool $modalOpen = false;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(CarParkDayCapacityMetrics $dayCapacityMetrics)
    {
        $query = CarPark::addSelect($dayCapacityMetrics->listSelectSubqueries());

        if ($this->search) {
            $query->where(function ($q): void {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('location', 'like', '%'.$this->search.'%');
            });
        }

        $allParks = (clone $query)->get();
        $capacityTotals = $this->capacityOverTotals($allParks);
        $dropOffCoachTotal = (int) $allParks->sum(fn (CarPark $park): int => (int) ($park->drop_off_coaches ?? 0));

        return view('livewire.admin.car-parks', [
            'carParks' => $query->paginate(10),
            'capacityOverTotals' => $capacityTotals,
            'dropOffCoachTotal' => $dropOffCoachTotal,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CarPark>  $parks
     * @return array{
     *     friday: array{over_base: int, over_hard: int},
     *     saturday: array{over_base: int, over_hard: int},
     *     sunday: array{over_base: int, over_hard: int},
     *     live: array{over_base: int, over_hard: int}
     * }
     */
    protected function capacityOverTotals($parks): array
    {
        $blank = ['over_base' => 0, 'over_hard' => 0];
        $totals = [
            'friday' => $blank,
            'saturday' => $blank,
            'sunday' => $blank,
            'live' => $blank,
        ];

        foreach ($parks as $park) {
            $overflow = $park->overflowCapacity();

            foreach (['friday', 'saturday', 'sunday'] as $day) {
                $assigned = (int) $park->{"assigned_{$day}"};
                $capacity = (int) $park->{"capacity_{$day}"};
                $totals[$day]['over_base'] += max(0, $assigned - $capacity);
                $totals[$day]['over_hard'] += max(0, $assigned - $capacity - $overflow);
            }

            $liveUsed = (int) $park->current_occupancy;
            $liveCapacity = $park->capacityForToday();
            $totals['live']['over_base'] += max(0, $liveUsed - $liveCapacity);
            $totals['live']['over_hard'] += max(0, $liveUsed - $liveCapacity - $overflow);
        }

        return $totals;
    }

    public function create(): void
    {
        $this->authorizeManage();
        $this->reset(
            'name',
            'capacityFriday',
            'capacitySaturday',
            'capacitySunday',
            'overflowCapacity',
            'location',
            'color',
            'travelDirections',
            'carParkId',
            'mapImage',
            'existingMapImage',
        );
        $this->overflowCapacity = '0';
        $this->modalOpen = true;
    }

    public function edit(CarPark $carPark): void
    {
        $this->authorizeManage();
        $this->carParkId = $carPark->id;
        $this->name = $carPark->name;
        $this->capacityFriday = (string) $carPark->capacity_friday;
        $this->capacitySaturday = (string) $carPark->capacity_saturday;
        $this->capacitySunday = (string) $carPark->capacity_sunday;
        $this->overflowCapacity = (string) $carPark->overflowCapacity();
        $this->location = (string) ($carPark->location ?? '');
        $this->color = (string) ($carPark->color ?? '');
        $this->travelDirections = (string) ($carPark->travel_directions ?? '');
        $this->existingMapImage = $carPark->map_image_path ?? '';
        $this->mapImage = null;
        $this->modalOpen = true;
    }

    public function save(CarParkMapImageStorage $mapImages): void
    {
        $this->authorizeManage();

        $this->validate([
            'name' => 'required|string|max:255',
            'capacityFriday' => 'required|integer|min:1',
            'capacitySaturday' => 'required|integer|min:1',
            'capacitySunday' => 'required|integer|min:1',
            'overflowCapacity' => 'required|integer|min:0',
            'location' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'travelDirections' => 'nullable|string|max:2000',
            'mapImage' => 'nullable|image|max:10240',
        ]);

        $data = [
            'name' => $this->name,
            'capacity_friday' => (int) $this->capacityFriday,
            'capacity_saturday' => (int) $this->capacitySaturday,
            'capacity_sunday' => (int) $this->capacitySunday,
            'overflow_capacity' => (int) $this->overflowCapacity,
            'location' => $this->location !== '' ? $this->location : null,
            'color' => $this->color !== '' ? $this->color : null,
            'travel_directions' => $this->normalizedTravelDirections(),
        ];

        if ($this->carParkId) {
            $carPark = CarPark::findOrFail($this->carParkId);
            $carPark->update($data);

            if ($this->mapImage) {
                $mapImages->replace($carPark, $this->mapImage);
            }
        } else {
            $storedPath = null;

            try {
                if ($this->mapImage) {
                    $storedPath = $mapImages->store($this->mapImage);
                    $data['map_image_path'] = $storedPath;
                }

                CarPark::create($data);
            } catch (\Throwable $exception) {
                if ($storedPath !== null) {
                    $mapImages->delete($storedPath);
                }

                throw $exception;
            }
        }

        $this->modalOpen = false;
        Flux::toast($this->carParkId ? 'Car Park updated successfully.' : 'Car Park created successfully.');
        $this->reset(
            'name',
            'capacityFriday',
            'capacitySaturday',
            'capacitySunday',
            'overflowCapacity',
            'location',
            'color',
            'travelDirections',
            'carParkId',
            'mapImage',
            'existingMapImage',
        );
        $this->overflowCapacity = '0';
    }

    public function delete(CarPark $carPark, CarParkMapImageStorage $mapImages): void
    {
        $this->authorizeManage();
        $mapImages->delete($carPark->map_image_path);
        $carPark->delete();
        Flux::toast('Car Park deleted successfully.');
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('car-parks.manage'), 403);
    }

    protected function normalizedTravelDirections(): ?string
    {
        $directions = trim($this->travelDirections);

        return $directions !== '' ? $directions : null;
    }
}
