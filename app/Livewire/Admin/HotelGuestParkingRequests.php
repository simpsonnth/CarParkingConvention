<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\HotelGuestParking\DeleteHotelGuestParkingRequest;
use App\Actions\HotelGuestParking\RejectHotelGuestParkingRequest;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class HotelGuestParkingRequests extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 25;

    /** pending | approved | rejected | all */
    public string $statusFilter = 'pending';

    /** any | has_ticket | no_ticket */
    public string $ticketFilter = 'any';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTicketFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            return;
        }

        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function setTicketFilter(string $filter): void
    {
        if (! in_array($filter, ['any', 'has_ticket', 'no_ticket'], true)) {
            return;
        }

        $this->ticketFilter = $filter;
        $this->resetPage();
    }

    public function decline(int $id, RejectHotelGuestParkingRequest $reject): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $request = HotelGuestParkingRequest::query()->findOrFail($id);

        try {
            $reject->execute($request, $user);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first()
                ?? __('management.hotel_guest_parking.already_actioned');
            try {
                Flux::toast($message, variant: 'danger');
            } catch (\Throwable) {
                session()->flash('error', $message);
            }

            return;
        }

        try {
            Flux::toast(__('management.hotel_guest_parking.declined'));
        } catch (\Throwable) {
            session()->flash('status', __('management.hotel_guest_parking.declined'));
        }
    }

    public function delete(int $id, DeleteHotelGuestParkingRequest $delete): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        $request = HotelGuestParkingRequest::query()->findOrFail($id);
        $delete->execute($request);

        try {
            Flux::toast(__('management.hotel_guest_parking.deleted'));
        } catch (\Throwable) {
            session()->flash('status', __('management.hotel_guest_parking.deleted'));
        }
    }

    public function render()
    {
        $base = HotelGuestParkingRequest::query();

        $pendingCount = (clone $base)->where('status', HotelGuestParkingRequest::STATUS_PENDING)->count();
        $approvedCount = (clone $base)->where('status', HotelGuestParkingRequest::STATUS_APPROVED)->count();
        $rejectedCount = (clone $base)->where('status', HotelGuestParkingRequest::STATUS_REJECTED)->count();
        $total = (clone $base)->count();

        $hasTicketPendingQuery = HotelGuestParkingRequest::query()
            ->where('status', HotelGuestParkingRequest::STATUS_PENDING);
        $this->scopeWhereHasExistingTicket($hasTicketPendingQuery);
        $hasTicketPendingCount = $hasTicketPendingQuery->count();

        $noTicketPendingCount = max(0, $pendingCount - $hasTicketPendingCount);

        $query = HotelGuestParkingRequest::query()
            ->with(['actionedByUser:id,name', 'carPark:id,name']);

        if ($this->statusFilter === 'pending') {
            $query->where('status', HotelGuestParkingRequest::STATUS_PENDING);
        } elseif ($this->statusFilter === 'approved') {
            $query->where('status', HotelGuestParkingRequest::STATUS_APPROVED);
        } elseif ($this->statusFilter === 'rejected') {
            $query->where('status', HotelGuestParkingRequest::STATUS_REJECTED);
        }

        if ($this->ticketFilter === 'has_ticket') {
            $this->scopeWhereHasExistingTicket($query);
        } elseif ($this->ticketFilter === 'no_ticket') {
            $this->scopeWhereMissingExistingTicket($query);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('vehicle_registration', 'like', $like)
                    ->orWhere('contact_number', 'like', $like);
            });
        }

        $rows = $query
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $pageVehicleRegs = $rows->getCollection()
            ->pluck('vehicle_registration')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $duplicateVehicleRegs = [];
        if ($pageVehicleRegs !== []) {
            $duplicateVehicleRegs = HotelGuestParkingRequest::query()
                ->whereIn('vehicle_registration', $pageVehicleRegs)
                ->selectRaw('vehicle_registration, COUNT(*) as aggregate')
                ->groupBy('vehicle_registration')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('vehicle_registration')
                ->all();
            $duplicateVehicleRegs = array_fill_keys($duplicateVehicleRegs, true);
        }

        /** @var array<string, ParkingRegistration> $existingTicketsByVehicleReg */
        $existingTicketsByVehicleReg = [];
        if ($pageVehicleRegs !== []) {
            ParkingRegistration::query()
                ->with('carPark:id,name,color')
                ->whereIn('vehicle_registration', $pageVehicleRegs)
                ->orderBy('id')
                ->get()
                ->each(function (ParkingRegistration $registration) use (&$existingTicketsByVehicleReg): void {
                    $key = (string) $registration->vehicle_registration;
                    if ($key !== '' && ! isset($existingTicketsByVehicleReg[$key])) {
                        $existingTicketsByVehicleReg[$key] = $registration;
                    }
                });
        }

        /** @var array<string, CarPark> $congregationCarParkByName */
        $congregationCarParkByName = [];
        $congregationNames = collect($existingTicketsByVehicleReg)
            ->map(fn (ParkingRegistration $registration): string => trim((string) $registration->congregation))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($congregationNames !== []) {
            Congregation::query()
                ->with('carPark:id,name,color')
                ->whereIn('name', $congregationNames)
                ->get(['id', 'name', 'car_park_id'])
                ->each(function (Congregation $congregation) use (&$congregationCarParkByName): void {
                    if ($congregation->carPark !== null) {
                        $congregationCarParkByName[trim((string) $congregation->name)] = $congregation->carPark;
                    }
                });
        }

        /** @var array<string, CarPark|null> $existingTicketCarParkByVehicleReg */
        $existingTicketCarParkByVehicleReg = [];
        foreach ($existingTicketsByVehicleReg as $vehicleReg => $registration) {
            $existingTicketCarParkByVehicleReg[$vehicleReg] = $registration->carPark
                ?? ($congregationCarParkByName[trim((string) $registration->congregation)] ?? null);
        }

        return view('livewire.admin.hotel-guest-parking-requests', [
            'rows' => $rows,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'total' => $total,
            'hasTicketPendingCount' => $hasTicketPendingCount,
            'noTicketPendingCount' => $noTicketPendingCount,
            'duplicateVehicleRegs' => $duplicateVehicleRegs,
            'existingTicketsByVehicleReg' => $existingTicketsByVehicleReg,
            'existingTicketCarParkByVehicleReg' => $existingTicketCarParkByVehicleReg,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\HotelGuestParkingRequest>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\HotelGuestParkingRequest>
     */
    private function scopeWhereHasExistingTicket($query)
    {
        return $query->whereExists(function ($q): void {
            $q->selectRaw('1')
                ->from('parking_registrations')
                ->whereColumn(
                    'parking_registrations.vehicle_registration',
                    'hotel_guest_parking_requests.vehicle_registration',
                )
                ->whereNull('parking_registrations.deleted_at');
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\HotelGuestParkingRequest>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\HotelGuestParkingRequest>
     */
    private function scopeWhereMissingExistingTicket($query)
    {
        return $query->whereNotExists(function ($q): void {
            $q->selectRaw('1')
                ->from('parking_registrations')
                ->whereColumn(
                    'parking_registrations.vehicle_registration',
                    'hotel_guest_parking_requests.vehicle_registration',
                )
                ->whereNull('parking_registrations.deleted_at');
        });
    }
}
