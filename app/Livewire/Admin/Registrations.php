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

    /** Sort: column key and direction */
    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';

    /** Filter panel visibility */
    public bool $filterOpen = false;

    /** Applied filters (used in query) */
    public array $filterCongregations = [];
    public array $filterCarParks = [];
    public array $filterVehicleType = [];
    /** @var bool|null true = yes, false = no, null = any */
    public $filterElderlyInfirm = null;

    /** Draft filter values (in fly-out before Apply) */
    public array $filterDraftCongregations = [];
    public array $filterDraftCarParks = [];
    public array $filterDraftVehicleType = [];
    /** @var string|null 'any' | '1' | '0' for draft panel */
    public $filterDraftElderlyInfirm = 'any';

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

    public function setSort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function openFilterPanel(): void
    {
        $this->filterDraftCongregations = $this->filterCongregations;
        $this->filterDraftCarParks = $this->filterCarParks;
        $this->filterDraftVehicleType = $this->filterVehicleType;
        $this->filterDraftElderlyInfirm = $this->filterElderlyInfirm === null ? 'any' : ($this->filterElderlyInfirm ? '1' : '0');
        $this->filterOpen = true;
    }

    public function applyFilters(): void
    {
        $this->filterCongregations = $this->filterDraftCongregations;
        $this->filterCarParks = array_map('intval', $this->filterDraftCarParks);
        $this->filterVehicleType = $this->filterDraftVehicleType;
        $draft = $this->filterDraftElderlyInfirm;
        $this->filterElderlyInfirm = ($draft === 'any' || $draft === '' || $draft === null) ? null : (bool) (int) $draft;
        $this->filterOpen = false;
        $this->resetPage();
    }

    public function cancelFilters(): void
    {
        $this->filterOpen = false;
    }

    public function clearFilters(): void
    {
        $this->filterCongregations = [];
        $this->filterCarParks = [];
        $this->filterVehicleType = [];
        $this->filterElderlyInfirm = null;
        $this->filterDraftCongregations = [];
        $this->filterDraftCarParks = [];
        $this->filterDraftVehicleType = [];
        $this->filterDraftElderlyInfirm = 'any';
        $this->resetPage();
    }

    public function getAppliedFiltersCount(): int
    {
        $n = 0;
        if (! empty($this->filterCongregations)) {
            $n += count($this->filterCongregations);
        }
        if (! empty($this->filterCarParks)) {
            $n += count($this->filterCarParks);
        }
        if (! empty($this->filterVehicleType)) {
            $n += count($this->filterVehicleType);
        }
        if ($this->filterElderlyInfirm !== null) {
            $n += 1;
        }
        return $n;
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

    /** Download a ZIP of master pass PDFs for the selected registrations (redirects to download URL). */
    public function downloadMasterPassesZip()
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            return;
        }

        $token = \Illuminate\Support\Str::random(32);
        $ids = array_values(array_map('intval', $this->selectedIds));
        cache()->put('master-passes-zip:' . $token, $ids, now()->addMinutes(2));

        try {
            return $this->redirect(route('admin.registrations.download-passes-zip', ['token' => $token]), navigate: false);
        } catch (\Throwable $e) {
            cache()->forget('master-passes-zip:' . $token);
            Flux::toast($e->getMessage(), variant: 'danger');
            return null;
        }
    }

    protected function getRegistrationsQuery()
    {
        $query = ParkingRegistration::query()
            ->with('carPark')
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('vehicle_registration', 'like', '%' . $this->search . '%')
                        ->orWhere('congregation', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when(! empty($this->filterCongregations), function ($q) {
                $q->whereIn('congregation', $this->filterCongregations);
            })
            ->when(! empty($this->filterCarParks), function ($q) {
                $q->whereIn('car_park_id', $this->filterCarParks);
            })
            ->when(! empty($this->filterVehicleType), function ($q) {
                $q->whereIn('vehicle_type', $this->filterVehicleType);
            })
            ->when($this->filterElderlyInfirm !== null, function ($q) {
                $q->where('elderly_infirm_parking', $this->filterElderlyInfirm);
            });

        $sortColumn = match ($this->sortBy) {
            'name' => 'name',
            'congregation' => 'congregation',
            'created_at' => 'created_at',
            'vehicle_registration' => 'vehicle_registration',
            default => 'created_at',
        };
        $query->orderBy($sortColumn, $this->sortDir === 'desc' ? 'desc' : 'asc');

        return $query;
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
