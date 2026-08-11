<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class GenericParkingQrCodes extends Component
{
    public string $search = '';

    public ?int $guestCarParkId = null;

    public function mount(): void
    {
        $this->guestCarParkId = $this->defaultGuestCarParkId();
    }

    /**
     * @return Collection<int, Congregation>
     */
    public function congregations(): Collection
    {
        return Congregation::query()
            ->with('carPark')
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('uuid', 'like', $term);
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, CarPark>
     */
    public function carParks(): Collection
    {
        return CarPark::query()->orderBy('name')->get();
    }

    public function selectedGuestCarPark(): ?CarPark
    {
        if ($this->guestCarParkId === null) {
            return null;
        }

        return $this->carParks()->firstWhere('id', $this->guestCarParkId);
    }

    public function render()
    {
        $guestPark = $this->selectedGuestCarPark();

        return view('livewire.admin.generic-parking-qr-codes', [
            'congregations' => $this->congregations(),
            'carParks' => $this->carParks(),
            'guestPark' => $guestPark,
            'guestPrintUrl' => $guestPark
                ? route('admin.parking-qr-codes.print-guest', $guestPark)
                : null,
            'guestNavUrl' => $guestPark?->navigationUrl(),
            'walkInScanUrl' => route('attendant.scan.walk-in'),
            'coachWalkInScanUrl' => route('attendant.scan.walk-in.coach'),
            'convName' => Setting::get('convention_name', "Convention of Jehovah's Witness"),
            'convYear' => Setting::get('convention_year', date('Y')),
            'convLoc' => Setting::get('convention_location', 'Twickenham'),
            'ticketLogo' => Setting::get('ticket_logo'),
        ]);
    }

    protected function defaultGuestCarParkId(): ?int
    {
        $rosebine2 = CarPark::query()->where('name', 'Rosebine 2')->value('id');
        if ($rosebine2 !== null) {
            return (int) $rosebine2;
        }

        $withCoords = CarPark::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->value('id');
        if ($withCoords !== null) {
            return (int) $withCoords;
        }

        $first = CarPark::query()->orderBy('name')->value('id');

        return $first !== null ? (int) $first : null;
    }
}
