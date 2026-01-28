<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ParkingRegistration;
use Flux\Flux;

class Registrations extends Component
{
    use WithPagination;

    public $search = '';

    public bool $modalOpen = false;
    public ?ParkingRegistration $editingRegistration = null;

    // Form Fields
    public $name = '';
    public $congregation = '';
    public $vehicleReg = '';
    public $contactNumber = '';
    public $email = '';
    public $days = [];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function edit($id)
    {
        $this->editingRegistration = ParkingRegistration::findOrFail($id);

        $this->name = $this->editingRegistration->name;
        $this->congregation = $this->editingRegistration->congregation;
        $this->vehicleReg = $this->editingRegistration->vehicle_registration;
        $this->contactNumber = $this->editingRegistration->contact_number;
        $this->email = $this->editingRegistration->email;
        $this->days = $this->editingRegistration->days ?? [];

        $this->modalOpen = true;
    }

    public function delete($id)
    {
        $reg = ParkingRegistration::findOrFail($id);
        $reg->delete();
        Flux::toast('Registration deleted successfully.');
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'congregation' => 'required|string|max:255',
            'vehicleReg' => 'required|string|min:2|max:20',
            'contactNumber' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'days' => 'nullable|array',
        ]);

        $this->vehicleReg = strtoupper(str_replace(' ', '', trim($this->vehicleReg)));

        if ($this->editingRegistration) {
            $this->editingRegistration->update([
                'name' => $this->name,
                'congregation' => $this->congregation,
                'vehicle_registration' => $this->vehicleReg,
                'contact_number' => $this->contactNumber,
                'email' => $this->email,
                'days' => $this->days,
            ]);

            Flux::toast('Registration updated successfully.');
        }

        $this->modalOpen = false;
        $this->reset('editingRegistration', 'name', 'congregation', 'vehicleReg', 'contactNumber', 'email', 'days');
    }

    public function toggleDay($day)
    {
        if (in_array($day, $this->days)) {
            $this->days = array_values(array_diff($this->days, [$day]));
        } else {
            $this->days[] = $day;
        }
    }

    public function render()
    {
        $registrations = ParkingRegistration::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('vehicle_registration', 'like', '%' . $this->search . '%')
                    ->orWhere('congregation', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.registrations', [
            'registrations' => $registrations,
            'congregations' => \App\Models\Congregation::orderBy('name')->pluck('name'),
        ]);
    }
}
