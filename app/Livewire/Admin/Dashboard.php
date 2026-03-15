<?php

namespace App\Livewire\Admin;

use App\Models\CarPark;
use App\Models\ParkingPass;
use Illuminate\Support\Facades\DB;
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
            'current_occupancy' => DB::raw("(select count(*) from parking_passes left join congregations on congregations.id = parking_passes.congregation_id where parking_passes.status = 'parked' and (parking_passes.car_park_id = car_parks.id or (parking_passes.car_park_id is null and congregations.car_park_id = car_parks.id)))"),
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
            $parkedCars = ParkingPass::parkedAtCarPark($this->viewParkId)
                ->with('congregation', 'carPark')
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
