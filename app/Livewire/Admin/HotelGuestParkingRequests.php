<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\HotelGuestParking\DeleteHotelGuestParkingRequest;
use App\Actions\HotelGuestParking\RejectHotelGuestParkingRequest;
use App\Actions\Registrations\SendCarParkTicketsEmail;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use App\Services\CarParkDayCapacityMetrics;
use App\Support\TicketEmailCcList;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

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

    /** created_at | name | vehicle_registration */
    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    public bool $resendModalOpen = false;

    public ?int $resendRequestId = null;

    public string $resendEmailTo = '';

    public string $resendOriginalEmail = '';

    public string $resendGuestName = '';

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

    public function setSort(string $column): void
    {
        if (! in_array($column, ['created_at', 'name', 'vehicle_registration'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }

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

    public function openResendModal(int $id): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        $request = HotelGuestParkingRequest::query()->findOrFail($id);

        if (! $request->isApproved() || $request->parking_registration_id === null) {
            try {
                Flux::toast(__('management.hotel_guest_parking.resend_unavailable'), variant: 'warning');
            } catch (\Throwable) {
                session()->flash('error', __('management.hotel_guest_parking.resend_unavailable'));
            }

            return;
        }

        $this->resetErrorBag('resendEmailTo');
        $this->resendRequestId = (int) $request->id;
        $this->resendOriginalEmail = (string) $request->email;
        $this->resendEmailTo = (string) $request->email;
        $this->resendGuestName = (string) $request->name;
        $this->resendModalOpen = true;
    }

    public function closeResendModal(): void
    {
        $this->resendModalOpen = false;
        $this->resendRequestId = null;
        $this->resendEmailTo = '';
        $this->resendOriginalEmail = '';
        $this->resendGuestName = '';
        $this->resetErrorBag('resendEmailTo');
    }

    public function useOriginalResendEmail(): void
    {
        $this->resendEmailTo = $this->resendOriginalEmail;
        $this->resetErrorBag('resendEmailTo');
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function ticketEmailCcs(): array
    {
        return TicketEmailCcList::all();
    }

    public function resendTicket(SendCarParkTicketsEmail $sender): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        $this->validate([
            'resendEmailTo' => 'required|email|max:255',
        ], [
            'resendEmailTo.required' => __('management.hotel_guest_parking.resend_email_required'),
            'resendEmailTo.email' => __('management.hotel_guest_parking.resend_email_invalid'),
        ]);

        if ($this->resendRequestId === null) {
            $this->closeResendModal();

            return;
        }

        $request = HotelGuestParkingRequest::query()->findOrFail($this->resendRequestId);

        if (! $request->isApproved() || $request->parking_registration_id === null) {
            $this->addError('resendEmailTo', __('management.hotel_guest_parking.resend_unavailable'));

            return;
        }

        try {
            $result = $sender->execute(
                [(int) $request->parking_registration_id],
                $this->resendEmailTo,
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->addError('resendEmailTo', $message);
                }
            }

            return;
        } catch (Throwable $e) {
            try {
                Flux::toast($e->getMessage(), variant: 'danger');
            } catch (\Throwable) {
                session()->flash('error', $e->getMessage());
            }

            return;
        }

        $this->closeResendModal();

        try {
            Flux::toast(__('management.hotel_guest_parking.resend_sent', [
                'email' => $result['to'],
            ]));
        } catch (\Throwable) {
            session()->flash('status', __('management.hotel_guest_parking.resend_sent', [
                'email' => $result['to'],
            ]));
        }
    }

    public function render(CarParkDayCapacityMetrics $dayCapacityMetrics)
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
            ->tap(fn ($q) => $this->applySort($q))
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
            'carParkCapacityRows' => CarPark::query()
                ->addSelect($dayCapacityMetrics->listSelectSubqueries())
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\HotelGuestParkingRequest>  $query
     */
    private function applySort($query): void
    {
        $dir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        if ($this->sortBy === 'name') {
            $query->orderByRaw('LOWER(TRIM(name)) '.$dir)
                ->orderByDesc('created_at');

            return;
        }

        if ($this->sortBy === 'vehicle_registration') {
            $query->orderByRaw('LOWER(TRIM(vehicle_registration)) '.$dir)
                ->orderByDesc('created_at');

            return;
        }

        $query
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderBy('created_at', $dir);
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
