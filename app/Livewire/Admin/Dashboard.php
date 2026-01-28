<?php

namespace App\Livewire\Admin;

use App\Models\CarPark;
use App\Models\ParkingPass;
use Livewire\Component;

class Dashboard extends Component
{
    use \Livewire\WithPagination;

    #[\Livewire\Attributes\Url]
    public $viewParkId = null;
    public $viewCarsModal = false;

    public function mount()
    {
        if ($this->viewParkId) {
            $this->viewCarsModal = true;
        }
    }

    public function viewCars($parkId)
    {
        $this->viewParkId = $parkId;
        $this->viewCarsModal = true;
        $this->resetPage();
    }

    public function closeCarsModal()
    {
        $this->viewCarsModal = false;
        $this->viewParkId = null;
    }

    public function render()
    {
        $carParks = CarPark::addSelect([
            'current_occupancy' => ParkingPass::selectRaw('count(*)')
                ->join('congregations', 'congregations.id', '=', 'parking_passes.congregation_id')
                ->whereColumn('congregations.car_park_id', 'car_parks.id')
                ->where('parking_passes.status', 'parked')
        ])->get();

        $totalCapacity = $carParks->sum('capacity');
        $totalOccupancy = $carParks->sum('current_occupancy');
        $totalPercentage = $totalCapacity > 0 ? ($totalOccupancy / $totalCapacity) * 100 : 0;

        $recentScans = ParkingPass::with(['congregation', 'congregation.carPark'])
            ->where('status', 'parked')
            ->latest('scanned_at')
            ->take(8)
            ->get();

        $congregationStats = \App\Models\Congregation::withCount([
            'parkingPasses as parked_count' => function ($query) {
                $query->where('status', 'parked');
            }
        ])
            ->orderByDesc('parked_count')
            ->take(5)
            ->get();

        $parkedCars = null;
        if ($this->viewParkId) {
            // Fetch all parked cars for this car park
            $parkedCars = ParkingPass::where('status', 'parked')
                ->whereHas('congregation', function ($q) {
                    $q->where('car_park_id', $this->viewParkId);
                })
                ->with('congregation')
                ->latest('scanned_at')
                ->paginate(10);
        }

        return view('livewire.admin.dashboard', [
            'carParks' => $carParks,
            'totalCapacity' => $totalCapacity,
            'totalOccupancy' => $totalOccupancy,
            'totalPercentage' => $totalPercentage,
            'recentScans' => $recentScans,
            'congregationStats' => $congregationStats,
            'parkedCars' => $parkedCars,
            'selectedPark' => $this->viewParkId ? $carParks->firstWhere('id', $this->viewParkId) : null,
            'registrations' => \App\Models\ParkingRegistration::latest()->take(20)->get(),
        ]);
    }
}
