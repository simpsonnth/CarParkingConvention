<?php

namespace App\Livewire\Public;

use App\Models\Congregation;
use App\Models\ParkingRegistration;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Register extends Component
{
    public $name = '';
    public $congregation = '';
    public $contactNumber = '';
    public $vehicleReg = '';
    public $days = []; // ['Friday', 'Saturday', 'Sunday']
    public $email = '';

    public $registered = false;

    #[Layout('components.layouts.public')]
    public function render()
    {
        return view('livewire.public.register', [
            'congregations' => Congregation::orderBy('name')->get(),
        ]);
    }

    public function register()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'congregation' => 'required|string|max:255',
            'contactNumber' => 'required|string|max:255',
            'vehicleReg' => 'required|string|max:20|uppercase',
            'days' => 'required|array|min:1',
            'email' => 'required|email|max:255',
        ]);

        // Format Registration
        $formattedReg = strtoupper(str_replace(' ', '', trim($this->vehicleReg)));

        ParkingRegistration::create([
            'name' => $this->name,
            'congregation' => $this->congregation,
            'contact_number' => $this->contactNumber,
            'vehicle_registration' => $formattedReg,
            'days' => $this->days,
            'email' => $this->email,
        ]);

        $this->registered = true;

        try {
            Flux::toast('Registration Successful!');
        } catch (\Throwable $e) {
            // Fallback if Flux is not available or fails
            session()->flash('status', 'Registration Successful!');
        }
    }
}
