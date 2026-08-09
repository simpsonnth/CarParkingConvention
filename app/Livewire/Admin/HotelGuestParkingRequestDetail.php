<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\HotelGuestParking\ApproveHotelGuestParkingRequest;
use App\Actions\HotelGuestParking\DeleteHotelGuestParkingRequest;
use App\Actions\HotelGuestParking\RejectHotelGuestParkingRequest;
use App\Actions\Registrations\SendCarParkTicketsEmail;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use App\Services\CarParkDayCapacityMetrics;
use App\Services\ParkingRegistrationDuplicateSignals;
use App\Support\ConventionDay;
use App\Support\TicketEmailCcList;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
class HotelGuestParkingRequestDetail extends Component
{
    public HotelGuestParkingRequest $hotelGuestParkingRequest;

    public string $adminNotes = '';

    public bool $approveModalOpen = false;

    public string $approveCarParkId = '';

    public bool $resendModalOpen = false;

    public string $resendEmailTo = '';

    public function mount(HotelGuestParkingRequest $hotelGuestParkingRequest): void
    {
        $this->hotelGuestParkingRequest = $hotelGuestParkingRequest->load([
            'actionedByUser:id,name',
            'carPark:id,name',
            'parkingRegistration.carPark:id,name',
        ]);
        $this->adminNotes = $hotelGuestParkingRequest->admin_notes ?? '';
    }

    public function saveAdminNotes(): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        $this->validate([
            'adminNotes' => 'nullable|string|max:5000',
        ]);

        $this->hotelGuestParkingRequest->update([
            'admin_notes' => trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null,
        ]);
        $this->hotelGuestParkingRequest->refresh();

        try {
            Flux::toast(__('management.hotel_guest_parking.admin_notes_saved'));
        } catch (\Throwable) {
            session()->flash('status', __('management.hotel_guest_parking.admin_notes_saved'));
        }
    }

    public function openApproveModal(): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        if (! $this->hotelGuestParkingRequest->isPending()) {
            return;
        }

        $this->resetErrorBag();
        $this->approveCarParkId = (string) ($this->defaultCarParkId() ?? '');
        $this->approveModalOpen = true;
    }

    public function closeApproveModal(): void
    {
        $this->approveModalOpen = false;
        $this->approveCarParkId = '';
    }

    public function approve(ApproveHotelGuestParkingRequest $approve): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $this->validate([
            'approveCarParkId' => 'required|exists:car_parks,id',
            'adminNotes' => 'nullable|string|max:5000',
        ], [
            'approveCarParkId.required' => __('management.hotel_guest_parking.car_park_required'),
        ]);

        try {
            $this->hotelGuestParkingRequest = $approve->execute(
                $this->hotelGuestParkingRequest,
                $user,
                (int) $this->approveCarParkId,
                trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null,
            )->load(['actionedByUser:id,name', 'carPark:id,name', 'parkingRegistration']);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError((string) $field, $message);
                }
            }

            return;
        }

        $this->adminNotes = $this->hotelGuestParkingRequest->admin_notes ?? '';
        $this->closeApproveModal();

        try {
            Flux::toast(__('management.hotel_guest_parking.approved'));
        } catch (\Throwable) {
            session()->flash('status', __('management.hotel_guest_parking.approved'));
        }
    }

    public function decline(RejectHotelGuestParkingRequest $reject): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $this->validate([
            'adminNotes' => 'nullable|string|max:5000',
        ]);

        try {
            $this->hotelGuestParkingRequest = $reject->execute(
                $this->hotelGuestParkingRequest,
                $user,
                trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null,
            )->load(['actionedByUser:id,name', 'carPark:id,name', 'parkingRegistration']);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError((string) $field, $message);
                }
            }

            return;
        }

        $this->adminNotes = $this->hotelGuestParkingRequest->admin_notes ?? '';

        try {
            Flux::toast(__('management.hotel_guest_parking.declined'));
        } catch (\Throwable) {
            session()->flash('status', __('management.hotel_guest_parking.declined'));
        }
    }

    public function delete(DeleteHotelGuestParkingRequest $delete): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        $delete->execute($this->hotelGuestParkingRequest);

        try {
            Flux::toast(__('management.hotel_guest_parking.deleted'));
        } catch (\Throwable) {
            session()->flash('status', __('management.hotel_guest_parking.deleted'));
        }

        $this->redirect(route('admin.hotel-guest-parking'), navigate: true);
    }

    public function openResendModal(): void
    {
        abort_unless(auth()->user()?->can('hotel-guest-parking.manage'), 403);

        if (! $this->hotelGuestParkingRequest->isApproved() || $this->hotelGuestParkingRequest->parking_registration_id === null) {
            try {
                Flux::toast(__('management.hotel_guest_parking.resend_unavailable'), variant: 'warning');
            } catch (\Throwable) {
                session()->flash('error', __('management.hotel_guest_parking.resend_unavailable'));
            }

            return;
        }

        $this->resetErrorBag('resendEmailTo');
        $this->resendEmailTo = (string) $this->hotelGuestParkingRequest->email;
        $this->resendModalOpen = true;
    }

    public function closeResendModal(): void
    {
        $this->resendModalOpen = false;
        $this->resendEmailTo = '';
        $this->resetErrorBag('resendEmailTo');
    }

    public function useOriginalResendEmail(): void
    {
        $this->resendEmailTo = (string) $this->hotelGuestParkingRequest->email;
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

        if (! $this->hotelGuestParkingRequest->isApproved() || $this->hotelGuestParkingRequest->parking_registration_id === null) {
            $this->addError('resendEmailTo', __('management.hotel_guest_parking.resend_unavailable'));

            return;
        }

        try {
            $result = $sender->execute(
                [(int) $this->hotelGuestParkingRequest->parking_registration_id],
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

    /**
     * Existing parking registration for this request's vehicle (if any).
     * When present, approve updates that ticket instead of creating a duplicate.
     */
    #[Computed]
    public function existingRegistrationMatch(): ?ParkingRegistration
    {
        if (! $this->hotelGuestParkingRequest->isPending()) {
            return null;
        }

        $signals = app(ParkingRegistrationDuplicateSignals::class);

        $match = $signals->findActiveByNormalizedVehicleReg(
            $signals->normalizeVehicleRegistration($this->hotelGuestParkingRequest->vehicle_registration),
        );

        return $match?->loadMissing('carPark:id,name,color');
    }

    /**
     * Effective car park for the existing registration (override or congregation default).
     */
    #[Computed]
    public function existingRegistrationCarPark(): ?CarPark
    {
        $match = $this->existingRegistrationMatch;
        if ($match === null) {
            return null;
        }

        if ($match->carPark !== null) {
            return $match->carPark;
        }

        $congregationName = trim((string) $match->congregation);
        if ($congregationName === '') {
            return null;
        }

        return Congregation::query()
            ->with('carPark:id,name,color')
            ->whereRaw('TRIM(name) = ?', [$congregationName])
            ->first()
            ?->carPark;
    }

    /**
     * @return Collection<int, object{id: int, name: string, label: string}>
     */
    #[Computed]
    public function carParks(): Collection
    {
        $dayCapacityMetrics = app(CarParkDayCapacityMetrics::class);
        $requestDays = collect($this->hotelGuestParkingRequest->days ?? [])
            ->map(fn ($day): string => (string) $day)
            ->filter(fn (string $day): bool => in_array($day, ConventionDay::singleDayKeys(), true))
            ->unique()
            ->values();

        return CarPark::query()
            ->orderBy('name')
            ->get()
            ->map(function (CarPark $park) use ($dayCapacityMetrics, $requestDays) {
                $assigned = $dayCapacityMetrics->assignedCountsForPark((int) $park->id);
                $parts = [];

                foreach ($requestDays as $day) {
                    $key = strtolower($day);
                    $count = (int) ($assigned[$key] ?? 0);
                    $capacity = $park->capacityForDay($day);
                    $overBy = max(0, $count - $capacity);
                    $short = match ($day) {
                        ConventionDay::FRIDAY => 'Fri',
                        ConventionDay::SATURDAY => 'Sat',
                        ConventionDay::SUNDAY => 'Sun',
                        default => $day,
                    };
                    $part = $short.' '.$count.'/'.$capacity;
                    if ($overBy > 0) {
                        $part .= ' (+'.$overBy.')';
                    }
                    $parts[] = $part;
                }

                $label = $park->name;
                if ($parts !== []) {
                    $label .= ' — '.implode(', ', $parts);
                }

                return (object) [
                    'id' => (int) $park->id,
                    'name' => (string) $park->name,
                    'label' => $label,
                ];
            })
            ->values();
    }

    public function defaultCarParkId(): ?int
    {
        $northId = CarPark::query()
            ->where('name', 'like', '%North%')
            ->orderBy('name')
            ->value('id');

        if ($northId !== null) {
            return (int) $northId;
        }

        $fallback = CarPark::query()->orderBy('name')->value('id');

        return $fallback !== null ? (int) $fallback : null;
    }

    public function render()
    {
        $this->hotelGuestParkingRequest->refresh()->loadMissing([
            'actionedByUser:id,name',
            'carPark:id,name',
            'parkingRegistration',
        ]);

        return view('livewire.admin.hotel-guest-parking-request-detail');
    }
}
