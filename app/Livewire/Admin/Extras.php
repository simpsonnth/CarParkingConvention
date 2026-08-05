<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Extras\ConvertExtraToRegistration;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingExtra;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('components.layouts.app')]
class Extras extends Component
{
    use WithPagination;

    public string $search = '';

    /** pending | actioned | all */
    public string $statusFilter = 'pending';

    public bool $modalOpen = false;

    public bool $actionModalOpen = false;

    public ?int $editingId = null;

    public ?int $actioningId = null;

    public string $actionCarParkId = '';

    public string $name = '';

    public string $congregation = '';

    public string $vehicleReg = '';

    public string $contactNumber = '';

    public string $email = '';

    public string $elderlyInfirmParking = '0';

    /** @var list<string> */
    public array $days = [];

    public string $notes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        if (! in_array($status, ['pending', 'actioned', 'all'], true)) {
            return;
        }

        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function create(): void
    {
        abort_unless(auth()->user()?->can('extras.manage'), 403);

        $this->resetErrorBag();
        $this->editingId = null;
        $this->resetFormFields();
        $this->modalOpen = true;
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->can('extras.manage'), 403);

        $extra = ParkingExtra::query()->findOrFail($id);
        if (! $extra->isPending()) {
            Flux::toast(__('extras.cannot_edit_actioned'), variant: 'warning');

            return;
        }

        $this->resetErrorBag();
        $this->editingId = $extra->id;
        $this->name = $extra->name;
        $this->congregation = $extra->congregation;
        $this->vehicleReg = $extra->vehicle_registration ?? '';
        $this->contactNumber = $extra->contact_number;
        $this->email = $extra->email ?? '';
        $this->elderlyInfirmParking = $extra->elderly_infirm_parking ? '1' : '0';
        $this->days = $extra->days ?? [];
        $this->notes = $extra->notes ?? '';
        $this->modalOpen = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('extras.manage'), 403);

        if ($this->editingId !== null) {
            $existing = ParkingExtra::query()->findOrFail($this->editingId);
            if (! $existing->isPending()) {
                Flux::toast(__('extras.cannot_edit_actioned'), variant: 'warning');
                $this->modalOpen = false;

                return;
            }
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'congregation' => 'required|string|exists:congregations,name',
            'vehicleReg' => 'required|string|min:2|max:20',
            'contactNumber' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'elderlyInfirmParking' => 'in:0,1',
            'days' => 'required|array|min:1',
            'days.*' => 'in:Friday,Saturday,Sunday',
            'notes' => 'nullable|string|max:2000',
        ]);

        $payload = [
            'name' => trim($this->name),
            'congregation' => trim($this->congregation),
            'contact_number' => trim($this->contactNumber),
            'email' => trim($this->email) !== '' ? trim($this->email) : null,
            'vehicle_type' => 'car',
            'vehicle_registration' => strtoupper(str_replace(' ', '', trim($this->vehicleReg))),
            'days' => array_values($this->days),
            'elderly_infirm_parking' => filter_var($this->elderlyInfirmParking, FILTER_VALIDATE_BOOLEAN),
            'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
            'status' => ParkingExtra::STATUS_PENDING,
        ];

        if ($this->editingId !== null) {
            ParkingExtra::query()->whereKey($this->editingId)->where('status', ParkingExtra::STATUS_PENDING)->update($payload);
            Flux::toast(__('extras.updated'));
        } else {
            ParkingExtra::query()->create($payload);
            Flux::toast(__('extras.created'));
            $this->resetPage();
        }

        $this->modalOpen = false;
        $this->editingId = null;
        $this->resetFormFields();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('extras.manage'), 403);

        $extra = ParkingExtra::query()->findOrFail($id);
        if (! $extra->isPending()) {
            Flux::toast(__('extras.cannot_delete_actioned'), variant: 'warning');

            return;
        }

        $extra->delete();
        Flux::toast(__('extras.deleted'));
        $this->resetPage();
    }

    public function openActionModal(int $id): void
    {
        abort_unless(auth()->user()?->can('extras.manage'), 403);

        $extra = ParkingExtra::query()->findOrFail($id);
        if (! $extra->isPending()) {
            Flux::toast(__('extras.already_actioned'), variant: 'warning');

            return;
        }

        $this->resetErrorBag();
        $this->actioningId = $extra->id;
        $this->actionCarParkId = '';
        $this->actionModalOpen = true;
    }

    public function confirmAction(): void
    {
        abort_unless(auth()->user()?->can('extras.manage'), 403);

        if ($this->actioningId === null) {
            return;
        }

        $this->validate([
            'actionCarParkId' => 'required|exists:car_parks,id',
        ], [
            'actionCarParkId.required' => __('extras.car_park_required'),
        ]);

        $extra = ParkingExtra::query()->findOrFail($this->actioningId);

        try {
            $registration = app(ConvertExtraToRegistration::class)->execute(
                $extra,
                (int) $this->actionCarParkId,
                auth()->user(),
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            try {
                Flux::toast($e->getMessage(), variant: 'danger');
            } catch (Throwable) {
                session()->flash('error', $e->getMessage());
            }

            return;
        }

        $this->actionModalOpen = false;
        $this->actioningId = null;
        $this->actionCarParkId = '';

        try {
            Flux::toast(__('extras.actioned', [
                'ticket' => $registration->ticketNumber(),
            ]));
        } catch (Throwable) {
            session()->flash('status', __('extras.actioned', [
                'ticket' => $registration->ticketNumber(),
            ]));
        }
    }

    public function toggleDay(string $day): void
    {
        if (in_array($day, $this->days, true)) {
            $this->days = array_values(array_diff($this->days, [$day]));
        } else {
            $this->days[] = $day;
        }
    }

    #[Computed]
    public function congregations(): array
    {
        return Congregation::query()->orderBy('name')->pluck('name')->all();
    }

    #[Computed]
    public function carParks()
    {
        return CarPark::query()->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $query = ParkingExtra::query()
            ->with(['parkingRegistration:id', 'actionedByUser:id,name'])
            ->latest();

        if ($this->statusFilter === 'pending') {
            $query->where('status', ParkingExtra::STATUS_PENDING);
        } elseif ($this->statusFilter === 'actioned') {
            $query->where('status', ParkingExtra::STATUS_ACTIONED);
        }

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('congregation', 'like', $like)
                    ->orWhere('vehicle_registration', 'like', $like)
                    ->orWhere('contact_number', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        return view('livewire.admin.extras', [
            'extras' => $query->paginate(25),
        ]);
    }

    private function resetFormFields(): void
    {
        $this->name = '';
        $this->congregation = '';
        $this->vehicleReg = '';
        $this->contactNumber = '';
        $this->email = '';
        $this->elderlyInfirmParking = '0';
        $this->days = [];
        $this->notes = '';
    }
}
