<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\HotelGuestParking\DeleteHotelGuestParkingRequest;
use App\Actions\HotelGuestParking\RejectHotelGuestParkingRequest;
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

    public function setStatusFilter(string $status): void
    {
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            return;
        }

        $this->statusFilter = $status;
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

        $query = HotelGuestParkingRequest::query()
            ->with(['actionedByUser:id,name', 'carPark:id,name']);

        if ($this->statusFilter === 'pending') {
            $query->where('status', HotelGuestParkingRequest::STATUS_PENDING);
        } elseif ($this->statusFilter === 'approved') {
            $query->where('status', HotelGuestParkingRequest::STATUS_APPROVED);
        } elseif ($this->statusFilter === 'rejected') {
            $query->where('status', HotelGuestParkingRequest::STATUS_REJECTED);
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
                ->with('carPark:id,name')
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

        return view('livewire.admin.hotel-guest-parking-requests', [
            'rows' => $rows,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'total' => $total,
            'duplicateVehicleRegs' => $duplicateVehicleRegs,
            'existingTicketsByVehicleReg' => $existingTicketsByVehicleReg,
        ]);
    }
}
