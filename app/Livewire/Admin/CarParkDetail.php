<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\CarParks\CarParkMapImageStorage;
use App\Models\CarPark;
use App\Models\ParkingPass;
use App\Services\CarParkDayCapacityMetrics;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CarParkDetail extends Component
{
    use WithFileUploads;
    use WithPagination;

    public CarPark $carPark;

    public string $name = '';

    public string $capacityFriday = '';

    public string $capacitySaturday = '';

    public string $capacitySunday = '';

    public string $location = '';

    public string $color = '';

    public string $travelDirections = '';

    public $mapImage = null;

    public string $existingMapImage = '';

    public bool $modalOpen = false;

    public ?ParkingPass $viewingPass = null;

    public bool $detailsModalOpen = false;

    public function viewDetails($passId): void
    {
        $this->viewingPass = ParkingPass::with('congregation')->find($passId);
        $this->detailsModalOpen = true;
    }

    public function edit(): void
    {
        $this->authorizeManage();
        $this->name = $this->carPark->name;
        $this->capacityFriday = (string) $this->carPark->capacity_friday;
        $this->capacitySaturday = (string) $this->carPark->capacity_saturday;
        $this->capacitySunday = (string) $this->carPark->capacity_sunday;
        $this->location = (string) ($this->carPark->location ?? '');
        $this->color = (string) ($this->carPark->color ?? '');
        $this->travelDirections = (string) ($this->carPark->travel_directions ?? '');
        $this->existingMapImage = $this->carPark->map_image_path ?? '';
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

        $this->carPark->update($data);

        if ($this->mapImage) {
            $mapImages->replace($this->carPark, $this->mapImage);
        }

        $this->carPark->refresh();
        $this->existingMapImage = $this->carPark->map_image_path ?? '';
        $this->reset('mapImage');

        $this->modalOpen = false;
        Flux::toast('Car Park details updated successfully.');
    }

    public function checkout($passId): void
    {
        $this->authorizeManage();
        $pass = ParkingPass::findOrFail($passId);
        $pass->update([
            'status' => 'left',
            'left_at' => now(),
        ]);
        Flux::toast('Vehicle checked out.');
    }

    public function checkoutAll(): void
    {
        $this->authorizeManage();
        ParkingPass::parkedAtCarPark($this->carPark->id)->update([
            'status' => 'left',
            'left_at' => now(),
        ]);

        Flux::toast('All vehicles checked out.');
    }

    public function mount(CarPark $carPark): void
    {
        $this->carPark = $carPark;
    }

    public function render(CarParkDayCapacityMetrics $dayCapacityMetrics)
    {
        $capacity = $this->carPark->capacityForToday();

        $occupancyQuery = ParkingPass::parkedAtCarPark($this->carPark->id);

        $occupancy = $occupancyQuery->count();

        $percentage = $capacity > 0 ? ($occupancy / $capacity) * 100 : 0;

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
                },
            ])
            ->orderByDesc('parked_count')
            ->get();

        $dayAssigned = $dayCapacityMetrics->assignedCountsForPark($this->carPark->id);
        $dayDropOff = $dayCapacityMetrics->dropOffCoachCountsForPark($this->carPark->id);

        return view('livewire.admin.car-park-detail', [
            'occupancy' => $occupancy,
            'capacity' => $capacity,
            'percentage' => $percentage,
            'parkedCars' => $parkedCars,
            'history' => $history,
            'congregationBreakdown' => $congregationBreakdown,
            'dayAssigned' => $dayAssigned,
            'dayDropOff' => $dayDropOff,
        ]);
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
