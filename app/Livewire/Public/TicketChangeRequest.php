<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Actions\TicketChangeRequests\SubmitTicketChangeRequest;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Models\TicketChangeRequest as TicketChangeRequestModel;
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

    public string $confirmOwnership = '';

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

    public function updatedCongregationCode(): void
    {
        $this->parkingRegistrationId = '';
        $this->confirmOwnership = '';
        $this->resetFieldEditors();
        unset($this->resolvedCongregation, $this->registrations);
    }

    public function updatedRequestType(): void
    {
        $this->parkingRegistrationId = '';
        $this->confirmOwnership = '';
        $this->notes = '';
        $this->resetFieldEditors();
        $this->resetAdditionFields();
    }

    public function updatedParkingRegistrationId(): void
    {
        $this->confirmOwnership = '';
        $this->hydrateFieldEditorsFromRegistration();
    }

    public function submitAnother(): void
    {
        $this->reset([
            'congregationCode',
            'requestType',
            'parkingRegistrationId',
            'confirmOwnership',
            'notificationEmail',
            'notificationEmailConfirmation',
            'notes',
            'submitted',
            'submittedAutoApplied',
        ]);
        $this->resetFieldEditors();
        $this->resetAdditionFields();
        unset($this->resolvedCongregation, $this->registrations);
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

        return Congregation::query()->where('uuid', $code)->first();
    }

    /**
     * @return list<array{id: int, label: string, ticket: string, name: string, vehicle_registration: string, vehicle_type: string, email: string, contact_number: string}>
     */
    #[Computed]
    public function registrations(): array
    {
        $congregation = $this->resolvedCongregation;
        if ($congregation === null) {
            return [];
        }

        return ParkingRegistration::query()
            ->whereRaw('LOWER(TRIM(congregation)) = ?', [mb_strtolower(trim($congregation->name))])
            ->orderByRaw("CASE WHEN vehicle_type = 'coach' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'vehicle_registration',
                'vehicle_type',
                'email',
                'contact_number',
            ])
            ->map(function (ParkingRegistration $registration): array {
                $vrn = (string) ($registration->vehicle_registration ?? '');
                $ticket = $registration->ticketNumber();
                $isCoach = ($registration->vehicle_type ?? 'car') === 'coach';
                $typeLabel = $isCoach
                    ? __('ticket_change_request.vehicle_coach')
                    : __('ticket_change_request.vehicle_car');

                if ($isCoach) {
                    $label = trim($ticket.' — '.$typeLabel.' — '.$registration->name);
                } else {
                    $label = trim($ticket.' — '.$typeLabel.' — '.$registration->name.($vrn !== '' ? ' ('.$vrn.')' : ''));
                }

                return [
                    'id' => $registration->id,
                    'label' => $label,
                    'ticket' => $ticket,
                    'name' => (string) $registration->name,
                    'vehicle_registration' => $vrn,
                    'vehicle_type' => (string) ($registration->vehicle_type ?: 'car'),
                    'email' => (string) ($registration->email ?? ''),
                    'contact_number' => (string) ($registration->contact_number ?? ''),
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

        return collect($this->registrations)->firstWhere('id', $id);
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

    private function hydrateFieldEditorsFromRegistration(): void
    {
        $selected = $this->selectedRegistration;

        if ($selected === null) {
            $this->resetFieldEditors();

            return;
        }

        $this->newName = $selected['name'];
        $this->newVehicleRegistration = $selected['vehicle_registration'];
        $this->newEmail = $selected['email'];
        $this->newContactNumber = $selected['contact_number'];
        $this->newVehicleType = $selected['vehicle_type'] ?: 'car';
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
