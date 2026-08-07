<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Actions\TicketChangeRequests\SubmitTicketChangeRequest;
use App\Models\Congregation;
use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use App\Models\TicketChangeRequest as TicketChangeRequestModel;
use App\Support\PersonNameMasker;
use App\Support\VehicleRegistrationMasker;
use App\Support\VehicleRegistrationNormalizer;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class TicketChangeRequest extends Component
{
    public string $congregationCode = '';

    public string $requestType = '';

    /** Select values arrive as strings from HTML. */
    public string $parkingRegistrationId = '';

    public string $registrationSearch = '';

    public string $confirmOwnership = '';

    public bool $ownershipVerified = false;

    public string $notificationEmail = '';

    public string $notificationEmailConfirmation = '';

    public string $notes = '';

    /** Field update toggles / values */
    public bool $changeName = false;

    public bool $changeVehicleRegistration = false;

    public bool $changeEmail = false;

    public bool $changeContactNumber = false;

    public bool $changeVehicleType = false;

    public string $newName = '';

    public string $newVehicleRegistration = '';

    public string $newEmail = '';

    public string $newContactNumber = '';

    public string $newVehicleType = 'car';

    /** Addition fields */
    public string $additionName = '';

    public string $additionContactNumber = '';

    public string $additionEmail = '';

    public string $additionVehicleType = 'car';

    public string $additionVehicleRegistration = '';

    /** @var list<string> */
    public array $additionDays = [];

    public bool $additionElderlyInfirm = false;

    public bool $submitted = false;

    public bool $submittedAutoApplied = false;

    public function mount(): void
    {
        $guest = strtolower(trim((string) request()->query('guest', '')));
        $code = strtoupper(trim((string) request()->query('code', '')));

        if ($guest === 'radisson' || $code === HotelGuestParkingRequest::PUBLIC_CODE) {
            $this->useRadissonHotelGuest();
        }
    }

    public function updatedCongregationCode(): void
    {
        $this->clearRegistrationSelection();
        unset($this->resolvedCongregation, $this->registrationSearchResults);
    }

    public function updatedRequestType(): void
    {
        $this->clearRegistrationSelection();
        $this->notes = '';
        $this->resetAdditionFields();
        unset($this->registrationSearchResults);
    }

    public function updatedRegistrationSearch(): void
    {
        $this->parkingRegistrationId = '';
        $this->confirmOwnership = '';
        $this->ownershipVerified = false;
        $this->resetFieldEditors();
        unset($this->registrationSearchResults, $this->selectedRegistration);
    }

    public function updatedParkingRegistrationId(): void
    {
        $this->confirmOwnership = '';
        $this->ownershipVerified = false;
        $this->resetFieldEditors();
    }

    public function updatedConfirmOwnership(): void
    {
        $this->ownershipVerified = false;
        $this->resetFieldEditors();
        $this->tryVerifyOwnership();
    }

    public function selectRegistration(int $id): void
    {
        $this->resetErrorBag('parkingRegistrationId');

        $congregation = $this->resolvedCongregation;
        if ($congregation === null) {
            return;
        }

        $registration = ParkingRegistration::query()
            ->whereKey($id)
            ->whereRaw('LOWER(TRIM(congregation)) = ?', [mb_strtolower(trim($congregation->name))])
            ->first();

        if ($registration === null) {
            return;
        }

        $this->parkingRegistrationId = (string) $id;
        $this->confirmOwnership = '';
        $this->ownershipVerified = false;
        $this->resetFieldEditors();
        unset($this->selectedRegistration);

        // Exact vehicle-reg search → treat as ownership proof and open the update form.
        if (VehicleRegistrationNormalizer::matches(
            $this->registrationSearch,
            $registration->vehicle_registration,
            'car',
        )) {
            $this->confirmOwnership = (string) (VehicleRegistrationNormalizer::normalize(
                $this->registrationSearch,
                'car',
            ) ?? '');
            $this->tryVerifyOwnership();
        }
    }

    public function clearRegistrationSelection(): void
    {
        $this->parkingRegistrationId = '';
        $this->registrationSearch = '';
        $this->confirmOwnership = '';
        $this->ownershipVerified = false;
        $this->resetFieldEditors();
        unset($this->registrationSearchResults, $this->selectedRegistration);
    }

    public function submitAnother(): void
    {
        $this->reset([
            'congregationCode',
            'requestType',
            'parkingRegistrationId',
            'registrationSearch',
            'confirmOwnership',
            'ownershipVerified',
            'notificationEmail',
            'notificationEmailConfirmation',
            'notes',
            'submitted',
            'submittedAutoApplied',
        ]);
        $this->resetFieldEditors();
        $this->resetAdditionFields();
        unset($this->resolvedCongregation, $this->registrationSearchResults, $this->selectedRegistration);
    }

    public function useRadissonHotelGuest(): void
    {
        HotelGuestParkingRequest::ensureCongregation();
        $this->congregationCode = HotelGuestParkingRequest::PUBLIC_CODE;
        $this->clearRegistrationSelection();
        unset($this->resolvedCongregation);
    }

    public function submit(SubmitTicketChangeRequest $submit): void
    {
        $this->resetErrorBag();

        try {
            $row = $submit->execute($this->buildInput());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($this->mapErrorField((string) $field), $message);
                }
            }

            return;
        }

        $this->submitted = true;
        $this->submittedAutoApplied = $row->request_type === TicketChangeRequestModel::TYPE_FIELD_UPDATE
            && $row->isCompleted();

        try {
            Flux::toast(
                $this->submittedAutoApplied
                    ? __('ticket_change_request.complete_auto_title')
                    : __('ticket_change_request.complete_title')
            );
        } catch (\Throwable) {
            session()->flash(
                'status',
                $this->submittedAutoApplied
                    ? __('ticket_change_request.complete_auto_title')
                    : __('ticket_change_request.complete_title')
            );
        }
    }

    #[Computed]
    public function resolvedCongregation(): ?Congregation
    {
        $code = trim($this->congregationCode);
        if ($code === '') {
            return null;
        }

        return Congregation::query()
            ->whereRaw('LOWER(TRIM(uuid)) = ?', [mb_strtolower($code)])
            ->first();
    }

    /**
     * Search results for the current congregation — masked labels only (no full PII).
     *
     * @return list<array{id: int, label: string, ticket: string, vehicle_type: string}>
     */
    #[Computed]
    public function registrationSearchResults(): array
    {
        $congregation = $this->resolvedCongregation;
        $term = trim($this->registrationSearch);
        if ($congregation === null || mb_strlen($term) < 2) {
            return [];
        }

        $like = '%'.$term.'%';
        $vrnNorm = strtoupper(str_replace(' ', '', $term));
        $digits = preg_replace('/\D+/', '', $term) ?? '';
        $ticketId = ($digits !== '' && ctype_digit($digits)) ? (int) $digits : null;

        $query = ParkingRegistration::query()
            ->whereRaw('LOWER(TRIM(congregation)) = ?', [mb_strtolower(trim($congregation->name))])
            ->where(function ($q) use ($like, $vrnNorm, $ticketId): void {
                $q->where('name', 'like', $like)
                    ->orWhere('vehicle_registration', 'like', $like)
                    ->orWhereRaw("REPLACE(UPPER(COALESCE(vehicle_registration, '')), ' ', '') LIKE ?", ['%'.$vrnNorm.'%']);

                if ($ticketId !== null && $ticketId > 0) {
                    $q->orWhere('id', $ticketId);
                }
            })
            ->orderByRaw("CASE WHEN vehicle_type = 'coach' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'vehicle_registration', 'vehicle_type']);

        return $query
            ->map(function (ParkingRegistration $registration): array {
                $vrn = (string) ($registration->vehicle_registration ?? '');
                $ticket = $registration->ticketNumber();
                $isCoach = ($registration->vehicle_type ?? 'car') === 'coach';
                $typeLabel = $isCoach
                    ? __('ticket_change_request.vehicle_coach')
                    : __('ticket_change_request.vehicle_car');
                $maskedName = PersonNameMasker::mask((string) $registration->name);

                if ($isCoach) {
                    $label = trim($ticket.' — '.$typeLabel.' — '.$maskedName);
                } else {
                    $maskedVrn = VehicleRegistrationMasker::mask($vrn);
                    $label = trim($ticket.' — '.$typeLabel.' — '.$maskedName.($maskedVrn !== '' ? ' ('.$maskedVrn.')' : ''));
                }

                return [
                    'id' => $registration->id,
                    'label' => $label,
                    'ticket' => $ticket,
                    'vehicle_type' => (string) ($registration->vehicle_type ?: 'car'),
                ];
            })
            ->values()
            ->all();
    }

    #[Computed]
    public function selectedRegistration(): ?array
    {
        if ($this->parkingRegistrationId === '') {
            return null;
        }

        $id = (int) $this->parkingRegistrationId;
        $fromSearch = collect($this->registrationSearchResults)->firstWhere('id', $id);
        if ($fromSearch !== null) {
            return $fromSearch;
        }

        $registration = ParkingRegistration::query()->find($id);
        if ($registration === null) {
            return null;
        }

        $vrn = (string) ($registration->vehicle_registration ?? '');
        $ticket = $registration->ticketNumber();
        $isCoach = ($registration->vehicle_type ?? 'car') === 'coach';
        $typeLabel = $isCoach
            ? __('ticket_change_request.vehicle_coach')
            : __('ticket_change_request.vehicle_car');
        $maskedName = PersonNameMasker::mask((string) $registration->name);
        $label = $isCoach
            ? trim($ticket.' — '.$typeLabel.' — '.$maskedName)
            : trim($ticket.' — '.$typeLabel.' — '.$maskedName.' ('.VehicleRegistrationMasker::mask($vrn).')');

        return [
            'id' => $registration->id,
            'label' => $label,
            'ticket' => $ticket,
            'vehicle_type' => (string) ($registration->vehicle_type ?: 'car'),
        ];
    }

    public function render()
    {
        return view('livewire.public.ticket-change-request');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInput(): array
    {
        $congregation = $this->resolvedCongregation;

        $input = [
            'request_type' => $this->requestType,
            'congregation_code' => trim($this->congregationCode),
            'congregation' => $congregation?->name ?? '',
            'parking_registration_id' => $this->parkingRegistrationId !== '' ? (int) $this->parkingRegistrationId : null,
            'confirm_ownership' => $this->confirmOwnership,
            'notification_email' => $this->notificationEmail,
            'notification_email_confirmation' => $this->notificationEmailConfirmation,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->requestType === TicketChangeRequestModel::TYPE_FIELD_UPDATE) {
            $changes = [];
            if ($this->changeName) {
                $changes['name'] = $this->newName;
            }
            if ($this->changeVehicleRegistration) {
                $changes['vehicle_registration'] = $this->newVehicleRegistration;
            }
            if ($this->changeEmail) {
                $changes['email'] = $this->newEmail;
            }
            if ($this->changeContactNumber) {
                $changes['contact_number'] = $this->newContactNumber;
            }
            if ($this->changeVehicleType) {
                $changes['vehicle_type'] = $this->newVehicleType;
            }
            $input['changes'] = $changes;
        }

        if ($this->requestType === TicketChangeRequestModel::TYPE_ADDITION) {
            $input['addition'] = [
                'name' => $this->additionName,
                'contact_number' => $this->additionContactNumber,
                'email' => $this->additionEmail,
                'vehicle_type' => $this->additionVehicleType,
                'vehicle_registration' => $this->additionVehicleRegistration,
                'days' => $this->additionDays,
                'elderly_infirm_parking' => $this->additionElderlyInfirm,
            ];
        }

        return $input;
    }

    private function mapErrorField(string $field): string
    {
        return match ($field) {
            'congregation_code' => 'congregationCode',
            'parking_registration_id' => 'parkingRegistrationId',
            'confirm_ownership', 'confirm_vehicle_registration' => 'confirmOwnership',
            'notification_email' => 'notificationEmail',
            'notification_email_confirmation' => 'notificationEmailConfirmation',
            'request_type' => 'requestType',
            'addition.name' => 'additionName',
            'addition.contact_number' => 'additionContactNumber',
            'addition.email' => 'additionEmail',
            'addition.vehicle_type' => 'additionVehicleType',
            'addition.vehicle_registration' => 'additionVehicleRegistration',
            'addition.days' => 'additionDays',
            'changes' => 'changeName',
            default => $field,
        };
    }

    private function tryVerifyOwnership(): void
    {
        if ($this->parkingRegistrationId === '' || trim($this->confirmOwnership) === '') {
            return;
        }

        $registration = ParkingRegistration::query()->find((int) $this->parkingRegistrationId);
        if ($registration === null) {
            return;
        }

        $vehicleType = (string) ($registration->vehicle_type ?: 'car');
        $confirmed = false;

        if ($vehicleType === 'coach') {
            $entered = preg_replace('/\D+/', '', trim($this->confirmOwnership)) ?? '';
            $ticket = preg_replace('/\D+/', '', $registration->ticketNumber()) ?? '';
            $confirmed = $entered !== '' && $ticket !== '' && $entered === $ticket;
        } else {
            $confirmed = VehicleRegistrationNormalizer::matches(
                $this->confirmOwnership,
                $registration->vehicle_registration,
                'car',
            );
        }

        if (! $confirmed) {
            return;
        }

        $this->ownershipVerified = true;
        $this->hydrateFieldEditorsFromRegistration($registration);
    }

    private function hydrateFieldEditorsFromRegistration(ParkingRegistration $registration): void
    {
        $this->newName = (string) $registration->name;
        $this->newVehicleRegistration = (string) ($registration->vehicle_registration ?? '');
        $this->newEmail = (string) ($registration->email ?? '');
        $this->newContactNumber = (string) ($registration->contact_number ?? '');
        $this->newVehicleType = (string) ($registration->vehicle_type ?: 'car');
        $this->changeName = false;
        $this->changeVehicleRegistration = false;
        $this->changeEmail = false;
        $this->changeContactNumber = false;
        $this->changeVehicleType = false;
    }

    private function resetFieldEditors(): void
    {
        $this->changeName = false;
        $this->changeVehicleRegistration = false;
        $this->changeEmail = false;
        $this->changeContactNumber = false;
        $this->changeVehicleType = false;
        $this->newName = '';
        $this->newVehicleRegistration = '';
        $this->newEmail = '';
        $this->newContactNumber = '';
        $this->newVehicleType = 'car';
    }

    private function resetAdditionFields(): void
    {
        $this->additionName = '';
        $this->additionContactNumber = '';
        $this->additionEmail = '';
        $this->additionVehicleType = 'car';
        $this->additionVehicleRegistration = '';
        $this->additionDays = [];
        $this->additionElderlyInfirm = false;
    }
}
