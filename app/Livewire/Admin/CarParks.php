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

        $capacityTotals = $this->capacityOverTotals((clone $query)->get());

        return view('livewire.admin.car-parks', [
            'carParks' => $query->paginate(10),
            'capacityOverTotals' => $capacityTotals,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CarPark>  $parks
     * @return array{friday: int, saturday: int, sunday: int, live: int}
     */
    protected function capacityOverTotals($parks): array
    {
        $totals = [
            'friday' => 0,
            'saturday' => 0,
            'sunday' => 0,
            'live' => 0,
        ];

        foreach ($parks as $park) {
            $totals['friday'] += max(0, (int) $park->assigned_friday - (int) $park->capacity_friday);
            $totals['saturday'] += max(0, (int) $park->assigned_saturday - (int) $park->capacity_saturday);
            $totals['sunday'] += max(0, (int) $park->assigned_sunday - (int) $park->capacity_sunday);
            $totals['live'] += max(0, (int) $park->current_occupancy - $park->capacityForToday());
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
            'location',
            'color',
            'travelDirections',
            'carParkId',
            'mapImage',
            'existingMapImage',
        );
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
            'location',
            'color',
            'travelDirections',
            'carParkId',
            'mapImage',
            'existingMapImage',
        );
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
