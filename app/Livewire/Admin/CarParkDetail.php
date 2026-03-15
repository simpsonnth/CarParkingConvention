<?php

namespace App\Livewire\Admin;

use App\Models\CarPark;
use App\Models\ParkingPass;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Flux\Flux;

class CarParkDetail extends Component
{
    use WithPagination;

    public CarPark $carPark;

    public $name = '';
    public $capacity = '';
    public $location = '';
    public $color = '';
    public bool $modalOpen = false;

    public ?ParkingPass $viewingPass = null;
    public bool $detailsModalOpen = false;

    public function viewDetails($passId)
    {
        $this->viewingPass = ParkingPass::with('congregation')->find($passId);
        $this->detailsModalOpen = true;
    }

    public function edit()
    {
        $this->name = $this->carPark->name;
        $this->capacity = $this->carPark->capacity;
        $this->location = $this->carPark->location;
        $this->color = $this->carPark->color;
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

        $this->carPark->update([
            'name' => $this->name,
            'capacity' => $this->capacity,
            'location' => $this->location,
            'color' => $this->color,
        ]);

        $this->modalOpen = false;
        Flux::toast('Car Park details updated successfully.');
    }

    public function checkout($passId)
    {
        $pass = ParkingPass::findOrFail($passId);
        $pass->update([
            'status' => 'left',
            'left_at' => now(),
        ]);
        Flux::toast('Vehicle checked out.');
    }

    public function checkoutAll()
    {
        ParkingPass::parkedAtCarPark($this->carPark->id)->update([
            'status' => 'left',
            'left_at' => now(),
        ]);

        Flux::toast('All vehicles checked out.');
    }

    public function mount(CarPark $carPark)
    {
        $this->carPark = $carPark;
    }

    public function render()
    {
        // Calculate stats
        $capacity = $this->carPark->capacity;

        $occupancyQuery = ParkingPass::parkedAtCarPark($this->carPark->id);

        $occupancy = $occupancyQuery->count();

        $percentage = $capacity > 0 ? ($occupancy / $capacity) * 100 : 0;

        // Get parked cars
        $parkedCars = $occupancyQuery->with(['congregation', 'scannedBy'])
            ->latest('scanned_at')
            ->paginate(15, pageName: 'parked_page');

        $history = ParkingPass::where('status', 'left')
            ->where(function ($q) {
                $q->where('car_park_id', $this->carPark->id)
                    ->orWhere(function ($q2) {
                        $q2->whereNull('car_park_id')
                            ->whereHas('congregation', fn ($c) => $c->where('car_park_id', $this->carPark->id));
                    });
            })
            ->whereDate('left_at', now()->toDateString())
            ->with(['congregation', 'scannedBy'])
            ->latest('left_at')
            ->paginate(10, pageName: 'history_page');

        $congregationBreakdown = \App\Models\Congregation::where('car_park_id', $this->carPark->id)
            ->whereHas('parkingPasses', function ($query) {
                $query->where('status', 'parked');
            })
            ->withCount([
                'parkingPasses as parked_count' => function ($query) {
                    $query->where('status', 'parked');
                }
            ])
            ->orderByDesc('parked_count')
            ->get();

        return view('livewire.admin.car-park-detail', [
            'occupancy' => $occupancy,
            'percentage' => $percentage,
            'parkedCars' => $parkedCars,
            'history' => $history,
            'congregationBreakdown' => $congregationBreakdown
        ]);
    }
}
