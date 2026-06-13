<?php

namespace App\Livewire\Admin;

use App\Models\CarPark;
use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

class CarParks extends Component
{
    use WithPagination;

    public $name = '';
    public $capacity = '';
    public $location = '';
    public $color = '';
    public $carParkId = null;

    public bool $modalOpen = false;

    public $search = '';

    public function updatedSearch()
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
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('location', 'like', '%' . $this->search . '%');
        }

        return view('livewire.admin.car-parks', [
            'carParks' => $query->paginate(10),
        ]);
    }

    public function create()
    {
        $this->reset('name', 'capacity', 'location', 'color', 'carParkId');
        $this->modalOpen = true;
    }

    public function edit(CarPark $carPark)
    {
        $this->carParkId = $carPark->id;
        $this->name = $carPark->name;
        $this->capacity = $carPark->capacity;
        $this->location = $carPark->location;
        $this->color = $carPark->color;
        $this->modalOpen = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string',
            'color' => 'nullable|string|max:50',
        ]);

        if ($this->carParkId) {
            $carPark = CarPark::findOrFail($this->carParkId);
            $carPark->update([
                'name' => $this->name,
                'capacity' => $this->capacity,
                'location' => $this->location,
                'color' => $this->color,
            ]);
        } else {
            CarPark::create([
                'name' => $this->name,
                'capacity' => $this->capacity,
                'location' => $this->location,
                'color' => $this->color,
            ]);
        }

        $this->modalOpen = false;
        Flux::toast($this->carParkId ? 'Car Park updated successfully.' : 'Car Park created successfully.');
        $this->reset('name', 'capacity', 'location', 'color', 'carParkId');
    }

    public function delete(CarPark $carPark)
    {
        $carPark->delete();
        Flux::toast('Car Park deleted successfully.');
    }
}
