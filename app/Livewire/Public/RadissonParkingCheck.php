<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Actions\HotelGuestParking\LookupRadissonParkingStatus;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class RadissonParkingCheck extends Component
{
    public string $vehicleRegistration = '';

    public bool $searched = false;

    public bool $found = false;

    public ?string $carParkName = null;

    public function check(LookupRadissonParkingStatus $lookup): void
    {
        $this->validate([
            'vehicleRegistration' => 'required|string|max:32',
        ]);

        $result = $lookup->handle($this->vehicleRegistration);

        $this->searched = true;
        $this->found = $result['found'];
        $this->carParkName = $result['car_park_name'];
    }

    public function checkAnother(): void
    {
        $this->reset(['vehicleRegistration', 'searched', 'found', 'carParkName']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.public.radisson-parking-check');
    }
}
