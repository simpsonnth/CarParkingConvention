<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ParkingRegistration;
use Flux\Flux;

#[Layout('components.layouts.app')]
class Registrations extends Component
{
    use WithPagination;

    public $search = '';

    public array $selectedIds = [];

    public bool $modalOpen = false;
    public ?ParkingRegistration $editingRegistration = null;

    // Form Fields
    public $name = '';
    public $congregation = '';
    public $vehicleType = 'car';
    public $vehicleReg = '';
    public $contactNumber = '';
    public $email = '';
    public $elderlyInfirmParking = '0';
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
        $this->vehicleType = $this->editingRegistration->vehicle_type ?? 'car';
        $this->vehicleReg = $this->editingRegistration->vehicle_registration;
        $this->contactNumber = $this->editingRegistration->contact_number;
        $this->email = $this->editingRegistration->email ?? '';
        $this->elderlyInfirmParking = $this->editingRegistration->elderly_infirm_parking ? '1' : '0';
        $this->days = $this->editingRegistration->days ?? [];

        $this->modalOpen = true;
    }

    public function delete($id): void
    {
        ParkingRegistration::findOrFail($id)->delete();
        Flux::toast(__('registrations.deleted'));
    }

    public function toggleSelect(int $id): void
    {
        $key = array_search($id, $this->selectedIds);
        if ($key !== false) {
            array_splice($this->selectedIds, $key, 1);
            $this->selectedIds = array_values($this->selectedIds);
        } else {
            $this->selectedIds = array_values(array_merge($this->selectedIds, [$id]));
        }
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->getRegistrationsQuery()->paginate(15)->pluck('id')->all();
        if (count(array_intersect($this->selectedIds, $ids)) === count($ids)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $ids));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $ids)));
        }
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            return;
        }
        $count = ParkingRegistration::whereIn('id', $this->selectedIds)->delete();
        $this->selectedIds = [];
        Flux::toast(__('registrations.bulk_deleted', ['count' => $count]));
    }

    protected function getRegistrationsQuery()
    {
        return ParkingRegistration::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('vehicle_registration', 'like', '%' . $this->search . '%')
                    ->orWhere('congregation', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->latest();
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'congregation' => 'required|string|max:255',
            'vehicleType' => 'required|in:car,coach',
            'contactNumber' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'elderlyInfirmParking' => 'in:0,1',
            'days' => 'nullable|array',
        ];
        $rules['vehicleReg'] = $this->vehicleType === 'car' ? 'required|string|min:2|max:20' : 'nullable|string|max:20';
        $this->validate($rules);

        $this->vehicleReg = $this->vehicleType === 'car' && trim($this->vehicleReg ?? '') !== ''
            ? strtoupper(str_replace(' ', '', trim($this->vehicleReg)))
            : null;

        if ($this->editingRegistration) {
            $this->editingRegistration->update([
                'name' => $this->name,
                'congregation' => $this->congregation,
                'vehicle_type' => $this->vehicleType,
                'vehicle_registration' => $this->vehicleReg ?? null,
                'contact_number' => $this->contactNumber,
                'email' => $this->email,
                'elderly_infirm_parking' => filter_var($this->elderlyInfirmParking, FILTER_VALIDATE_BOOLEAN),
                'days' => $this->days,
            ]);

            Flux::toast(__('registrations.updated'));
        }

        $this->modalOpen = false;
        $this->reset('editingRegistration', 'name', 'congregation', 'vehicleType', 'vehicleReg', 'contactNumber', 'email', 'elderlyInfirmParking', 'days');
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
        $registrations = $this->getRegistrationsQuery()->paginate(15);

        return view('livewire.admin.registrations', [
            'registrations' => $registrations,
            'congregations' => \App\Models\Congregation::orderBy('name')->pluck('name'),
        ]);
    }
}
