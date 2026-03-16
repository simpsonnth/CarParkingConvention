<?php

namespace App\Livewire\Admin;

use App\Models\Congregation;
use App\Models\ParkingRegistration;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Registrations extends Component
{
    use WithPagination;

    public $search = '';

    public array $selectedIds = [];

    /** @var int Per-page options: 25, 50, 100 */
    public int $perPage = 25;

    public bool $modalOpen = false;
    public bool $bulkAssignCarParkModalOpen = false;
    public ?ParkingRegistration $editingRegistration = null;

    /** For bulk assign congregation to car park */
    public $bulkAssignCarParkId = '';

    /** For bulk assign selected registrations (individuals) to car park */
    public $bulkAssignIndividualCarParkId = '';

    // Form Fields
    public $name = '';
    public $congregation = '';
    public $carParkId = '';
    public $vehicleType = 'car';
    public $vehicleReg = '';
    public $contactNumber = '';
    public $email = '';
    public $elderlyInfirmParking = '0';
    public $sharingWithOtherCongregations = '0';
    public $sharingCongregationsNotes = '';
    public $days = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function edit($id)
    {
        $this->editingRegistration = ParkingRegistration::findOrFail($id);

        $this->name = $this->editingRegistration->name;
        $this->congregation = $this->editingRegistration->congregation;
        $this->carParkId = $this->editingRegistration->car_park_id ? (string) $this->editingRegistration->car_park_id : '';
        $this->vehicleType = $this->editingRegistration->vehicle_type ?? 'car';
        $this->vehicleReg = $this->editingRegistration->vehicle_registration;
        $this->contactNumber = $this->editingRegistration->contact_number;
        $this->email = $this->editingRegistration->email ?? '';
        $this->elderlyInfirmParking = $this->editingRegistration->elderly_infirm_parking ? '1' : '0';
        $this->sharingWithOtherCongregations = $this->editingRegistration->sharing_with_other_congregations ? '1' : '0';
        $this->sharingCongregationsNotes = $this->editingRegistration->sharing_congregations_notes ?? '';
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
        $ids = $this->getRegistrationsQuery()->paginate($this->perPage)->pluck('id')->all();
        if (count(array_intersect($this->selectedIds, $ids)) === count($ids)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $ids));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $ids)));
        }
    }

    public function bulkSetElderlyInfirm(string $value): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            return;
        }
        $value = $value === '1' ? true : false;
        $count = ParkingRegistration::whereIn('id', $this->selectedIds)->update(['elderly_infirm_parking' => $value]);
        $this->selectedIds = [];
        Flux::toast(__('registrations.bulk_elderly_infirm_updated', ['count' => $count, 'value' => $value ? __('registrations.yes') : __('registrations.no')]));
    }

    public function openBulkAssignCarParkModal(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            return;
        }
        $this->bulkAssignCarParkId = '';
        $this->bulkAssignCarParkModalOpen = true;
    }

    public function bulkAssignCongregationToCarPark(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            $this->bulkAssignCarParkModalOpen = false;
            return;
        }
        $this->validate(['bulkAssignCarParkId' => 'required|exists:car_parks,id']);
        $registrations = ParkingRegistration::whereIn('id', $this->selectedIds)->get();
        $congregationNames = $registrations->pluck('congregation')->unique()->filter()->values();
        $updated = 0;
        $notFound = [];
        foreach ($congregationNames as $name) {
            $congregation = Congregation::where('name', $name)->first();
            if ($congregation) {
                $congregation->update(['car_park_id' => $this->bulkAssignCarParkId]);
                $updated++;
            } else {
                $notFound[] = $name;
            }
        }
        $this->selectedIds = [];
        $this->bulkAssignCarParkModalOpen = false;
        $this->bulkAssignCarParkId = '';
        $msg = __('registrations.bulk_congregation_car_park_assigned', ['count' => $updated]);
        if (count($notFound) > 0) {
            $msg .= ' ' . __('registrations.bulk_congregation_not_found', ['names' => implode(', ', array_slice($notFound, 0, 5)) . (count($notFound) > 5 ? '…' : '')]);
        }
        Flux::toast($msg, variant: count($notFound) > 0 ? 'warning' : 'success');
    }

    /** Assign selected registrations (individuals) to a car park — e.g. for elderly/infirm. */
    public function bulkAssignSelectedToCarPark(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            return;
        }
        $this->validate(['bulkAssignIndividualCarParkId' => 'required|exists:car_parks,id']);
        $count = ParkingRegistration::whereIn('id', $this->selectedIds)->update(['car_park_id' => $this->bulkAssignIndividualCarParkId]);
        $this->selectedIds = [];
        $this->bulkAssignIndividualCarParkId = '';
        Flux::toast(__('registrations.bulk_individual_car_park_assigned', ['count' => $count]));
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
            ->with('carPark')
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
            'carParkId' => 'nullable|exists:car_parks,id',
            'vehicleType' => 'required|in:car,coach',
            'contactNumber' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'elderlyInfirmParking' => 'in:0,1',
            'days' => 'nullable|array',
        ];
        $rules['vehicleReg'] = $this->vehicleType === 'car' ? 'required|string|min:2|max:20' : 'nullable|string|max:20';
        if ($this->vehicleType === 'coach') {
            $rules['sharingWithOtherCongregations'] = 'required|in:0,1';
            $rules['sharingCongregationsNotes'] = $this->sharingWithOtherCongregations === '1' ? 'required|string|max:1000' : 'nullable|string|max:1000';
        }
        $this->validate($rules);

        $this->vehicleReg = $this->vehicleType === 'car' && trim($this->vehicleReg ?? '') !== ''
            ? strtoupper(str_replace(' ', '', trim($this->vehicleReg)))
            : null;

        $carParkId = $this->carParkId ? (int) $this->carParkId : null;
        $sharingWithOther = $this->vehicleType === 'coach' && $this->sharingWithOtherCongregations === '1';
        $sharingNotes = $sharingWithOther ? trim($this->sharingCongregationsNotes) : null;

        if ($this->editingRegistration) {
            $this->editingRegistration->update([
                'name' => $this->name,
                'congregation' => $this->congregation,
                'car_park_id' => $carParkId,
                'vehicle_type' => $this->vehicleType,
                'vehicle_registration' => $this->vehicleReg ?? null,
                'contact_number' => $this->contactNumber,
                'email' => $this->email,
                'elderly_infirm_parking' => filter_var($this->elderlyInfirmParking, FILTER_VALIDATE_BOOLEAN),
                'sharing_with_other_congregations' => $this->vehicleType === 'coach' ? filter_var($this->sharingWithOtherCongregations, FILTER_VALIDATE_BOOLEAN) : false,
                'sharing_congregations_notes' => $sharingNotes,
                'days' => $this->days,
            ]);

            Flux::toast(__('registrations.updated'));
        }

        $this->modalOpen = false;
        $this->reset('editingRegistration', 'name', 'congregation', 'carParkId', 'vehicleType', 'vehicleReg', 'contactNumber', 'email', 'elderlyInfirmParking', 'sharingWithOtherCongregations', 'sharingCongregationsNotes', 'days');
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
        $registrations = $this->getRegistrationsQuery()->paginate($this->perPage);

        return view('livewire.admin.registrations', [
            'registrations' => $registrations,
            'congregations' => Congregation::orderBy('name')->pluck('name'),
            'carParks' => \App\Models\CarPark::orderBy('name')->get(),
        ]);
    }
}
