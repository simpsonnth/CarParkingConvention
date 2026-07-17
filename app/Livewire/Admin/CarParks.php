<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\CarParks\CarParkMapImageStorage;
use App\Models\CarPark;
use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CarParks extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $name = '';

    public string $capacity = '';

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

    public function render()
    {
        $query = CarPark::addSelect([
            'current_occupancy' => ParkingPass::query()->selectRaw('count(*)')
                ->leftJoin('congregations', 'congregations.id', '=', 'parking_passes.congregation_id')
                ->where('parking_passes.status', 'parked')
                ->where(function ($q) {
                    $q->whereColumn('parking_passes.car_park_id', 'car_parks.id')
                        ->orWhere(function ($q2) {
                            $q2->whereNull('parking_passes.car_park_id')
                                ->whereColumn('congregations.car_park_id', 'car_parks.id');
                        });
                }),
            'assigned_count' => ParkingRegistration::query()->selectRaw('count(*)')
                ->leftJoin('congregations', fn ($join) => $join->whereRaw('TRIM(congregations.name) = TRIM(parking_registrations.congregation)'))
                ->where(function ($q) {
                    $q->whereColumn('parking_registrations.car_park_id', 'car_parks.id')
                        ->orWhere(function ($q2) {
                            $q2->whereNull('parking_registrations.car_park_id')
                                ->whereColumn('congregations.car_park_id', 'car_parks.id');
                        });
                }),
        ]);

        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('location', 'like', '%'.$this->search.'%');
        }

        return view('livewire.admin.car-parks', [
            'carParks' => $query->paginate(10),
        ]);
    }

    public function create(): void
    {
        $this->authorizeManage();
        $this->reset('name', 'capacity', 'location', 'color', 'travelDirections', 'carParkId', 'mapImage', 'existingMapImage');
        $this->modalOpen = true;
    }

    public function edit(CarPark $carPark): void
    {
        $this->authorizeManage();
        $this->carParkId = $carPark->id;
        $this->name = $carPark->name;
        $this->capacity = (string) $carPark->capacity;
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
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'travelDirections' => 'nullable|string|max:2000',
            'mapImage' => 'nullable|image|max:10240',
        ]);

        $data = [
            'name' => $this->name,
            'capacity' => $this->capacity,
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
        $this->reset('name', 'capacity', 'location', 'color', 'travelDirections', 'carParkId', 'mapImage', 'existingMapImage');
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
