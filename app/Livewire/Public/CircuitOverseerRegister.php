<?php

namespace App\Livewire\Public;

use App\Models\ParkingRegistration;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class CircuitOverseerRegister extends Component
{
    public string $name = '';

    public string $contactNumber = '';

    public string $vehicleReg = '';

    /** @var array<int,string> */
    public array $days = [];

    public string $email = '';

    public string $elderlyInfirmParking = '0';

    public bool $registered = false;

    /** @var list<string> */
    protected static array $allDays = ['Friday', 'Saturday', 'Sunday'];

    public function toggleAllDays(): void
    {
        if (count($this->days) === count(self::$allDays)) {
            $this->days = [];
        } else {
            $this->days = self::$allDays;
        }
    }

    public function render()
    {
        return view('livewire.public.circuit-overseer-register');
    }

    public function register(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'contactNumber' => 'required|string|max:255',
            'days' => 'required|array|min:1',
            'days.*' => 'in:Friday,Saturday,Sunday',
            'email' => 'required|email|max:255',
            'vehicleReg' => 'required|string|max:20',
            'elderlyInfirmParking' => 'required|in:0,1',
        ];

        $this->validate($rules);

        $formattedReg = strtoupper(str_replace(' ', '', trim($this->vehicleReg)));

        ParkingRegistration::query()->create([
            'name' => trim($this->name),
            'congregation' => 'Circuit Overseer',
            'is_circuit_overseer' => true,
            'contact_number' => trim($this->contactNumber),
            'vehicle_registration' => $formattedReg,
            'days' => $this->days,
            'email' => trim($this->email),
            'vehicle_type' => 'car',
            'sharing_with_other_congregations' => false,
            'sharing_congregations_notes' => null,
            'elderly_infirm_parking' => filter_var($this->elderlyInfirmParking, FILTER_VALIDATE_BOOLEAN),
        ]);

        $this->registered = true;

        try {
            Flux::toast(__('register.co_registration_complete'));
        } catch (\Throwable) {
            session()->flash('status', __('register.co_registration_complete'));
        }
    }
}
