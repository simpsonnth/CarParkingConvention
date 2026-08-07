<?php

declare(strict_types=1);

namespace App\Actions\TicketChangeRequests;

use App\Models\ParkingRegistration;
use App\Models\TicketChangeRequest;
use App\Rules\PersonalEmail;
use App\Support\VehicleRegistrationNormalizer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmitTicketChangeRequest
{
    public function __construct(
        protected ApplyFieldUpdates $applyFieldUpdates,
    ) {}

    /**
     * @param  array{
     *     request_type: string,
     *     congregation_code?: string,
     *     congregation?: string,
     *     parking_registration_id?: int|null,
     *     confirm_ownership?: string|null,
     *     confirm_vehicle_registration?: string|null,
     *     notification_email: string,
     *     notification_email_confirmation?: string,
     *     notes?: string|null,
     *     changes?: array<string, mixed>,
     *     addition?: array<string, mixed>,
     * }  $input
     */
    public function execute(array $input): TicketChangeRequest
    {
        $typeForRules = (string) ($input['request_type'] ?? '');
        $needsRegistration = ! in_array($typeForRules, [
            TicketChangeRequest::TYPE_ADDITION,
            TicketChangeRequest::TYPE_EMAIL_REQUEST,
        ], true);
        $isEmailRequest = $typeForRules === TicketChangeRequest::TYPE_EMAIL_REQUEST;

        $emailRules = ['required', 'email', 'max:255'];
        if (! $isEmailRequest) {
            $emailRules[] = new PersonalEmail;
        }

        $validated = Validator::make($input, [
            'request_type' => ['required', Rule::in(TicketChangeRequest::TYPES)],
            'congregation_code' => ['required', 'string', 'exists:congregations,uuid'],
            'parking_registration_id' => [
                Rule::requiredIf($needsRegistration),
                'nullable',
                'integer',
                'exists:parking_registrations,id',
            ],
            'confirm_ownership' => [
                Rule::requiredIf($needsRegistration),
                'nullable',
                'string',
                'max:64',
            ],
            'notification_email' => $emailRules,
            'notification_email_confirmation' => [
                Rule::requiredIf(! $isEmailRequest),
                'nullable',
                'email',
                function (string $attribute, mixed $value, \Closure $fail) use ($input, $isEmailRequest): void {
                    if ($isEmailRequest) {
                        return;
                    }
                    $email = strtolower(trim((string) ($input['notification_email'] ?? '')));
                    $confirm = strtolower(trim((string) $value));
                    if ($email === '' || $confirm !== $email) {
                        $fail(__('ticket_change_request.validation.email_confirmation'));
                    }
                },
            ],
            'notes' => [
                Rule::requiredIf(in_array($typeForRules, [
                    TicketChangeRequest::TYPE_CAR_PARK_CHANGE,
                    TicketChangeRequest::TYPE_EMAIL_REQUEST,
                ], true)),
                'nullable',
                'string',
                'max:5000',
            ],
            'changes' => [
                Rule::requiredIf($typeForRules === TicketChangeRequest::TYPE_FIELD_UPDATE),
                'nullable',
                'array',
            ],
            'addition' => [
                Rule::requiredIf($typeForRules === TicketChangeRequest::TYPE_ADDITION),
                'nullable',
                'array',
            ],
        ])->validate();

        $type = $validated['request_type'];
        $congregation = \App\Models\Congregation::query()
            ->where('uuid', trim((string) $validated['congregation_code']))
            ->first();

        if ($congregation === null) {
            throw ValidationException::withMessages([
                'congregation_code' => __('ticket_change_request.validation.invalid_congregation_code'),
            ]);
        }

        $congregationName = trim((string) $congregation->name);
        $notificationEmail = strtolower(trim((string) $validated['notification_email']));
        $notes = isset($validated['notes']) && trim((string) $validated['notes']) !== ''
            ? trim((string) $validated['notes'])
            : null;

        if (in_array($type, [TicketChangeRequest::TYPE_CAR_PARK_CHANGE, TicketChangeRequest::TYPE_EMAIL_REQUEST], true)
            && ($notes === null || mb_strlen($notes) < 10)) {
            throw ValidationException::withMessages([
                'notes' => $type === TicketChangeRequest::TYPE_EMAIL_REQUEST
                    ? __('ticket_change_request.validation.email_request_notes')
                    : __('ticket_change_request.validation.car_park_notes'),
            ]);
        }

        $registration = null;
        if ($needsRegistration) {
            $registration = ParkingRegistration::query()
                ->whereKey((int) $validated['parking_registration_id'])
                ->first();

            if ($registration === null) {
                throw ValidationException::withMessages([
                    'parking_registration_id' => __('ticket_change_request.validation.registration_required'),
                ]);
            }

            if (strcasecmp(trim((string) $registration->congregation), $congregationName) !== 0) {
                throw ValidationException::withMessages([
                    'parking_registration_id' => __('ticket_change_request.validation.registration_congregation'),
                ]);
            }

            $confirm = (string) ($validated['confirm_ownership'] ?? $input['confirm_vehicle_registration'] ?? '');
            if (! $this->ownershipMatches($registration, $confirm)) {
                throw ValidationException::withMessages([
                    'confirm_ownership' => ($registration->vehicle_type ?? 'car') === 'coach'
                        ? __('ticket_change_request.validation.ticket_mismatch')
                        : __('ticket_change_request.validation.vrn_mismatch'),
                ]);
            }
        }

        $payload = [];
        $displayName = $registration?->name ?? '';

        if ($type === TicketChangeRequest::TYPE_FIELD_UPDATE) {
            $changes = $this->normalizeChanges(
                is_array($validated['changes'] ?? null) ? $validated['changes'] : [],
                $registration,
            );
            $payload['changes'] = $changes;
            if (isset($changes['name']) && is_string($changes['name']) && trim($changes['name']) !== '') {
                $displayName = trim($changes['name']);
            }
        }

        if ($type === TicketChangeRequest::TYPE_ADDITION) {
            $addition = $this->normalizeAddition(
                is_array($validated['addition'] ?? null) ? $validated['addition'] : [],
            );
            $payload['addition'] = $addition;
            $displayName = $addition['name'];
        }

        if ($type === TicketChangeRequest::TYPE_EMAIL_REQUEST) {
            $local = strstr($notificationEmail, '@', true);
            $displayName = is_string($local) && $local !== '' ? $local : $notificationEmail;
            $payload['source'] = 'email';
        }

        if ($type === TicketChangeRequest::TYPE_CANCELLATION || $type === TicketChangeRequest::TYPE_CAR_PARK_CHANGE) {
            $payload['ticket_number'] = $registration?->ticketNumber();
            $payload['vehicle_registration'] = $registration?->vehicle_registration;
        }

        $row = TicketChangeRequest::query()->create([
            'request_type' => $type,
            'parking_registration_id' => $registration?->id,
            'name' => $displayName !== '' ? $displayName : 'Unknown',
            'congregation' => $congregationName,
            'notification_email' => $notificationEmail,
            'notes' => $notes ?? $this->defaultNotes($type, $registration),
            'payload' => $payload,
            'status' => TicketChangeRequest::STATUS_PENDING,
        ]);

        if ($type === TicketChangeRequest::TYPE_FIELD_UPDATE) {
            return $this->applyFieldUpdates->execute($row, sendEmail: true);
        }

        return $row;
    }

    private function ownershipMatches(ParkingRegistration $registration, string $confirm): bool
    {
        $confirm = trim($confirm);
        if ($confirm === '') {
            return false;
        }

        if (($registration->vehicle_type ?? 'car') === 'coach') {
            $ticket = $registration->ticketNumber();
            $digits = preg_replace('/\D+/', '', $confirm) ?? '';

            return $digits !== '' && (
                strcasecmp($confirm, $ticket) === 0
                || strcasecmp(ltrim($digits, '0'), ltrim($ticket, '0')) === 0
                || $digits === $ticket
            );
        }

        return VehicleRegistrationNormalizer::matches(
            $confirm,
            $registration->vehicle_registration,
            'car',
        );
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function normalizeChanges(array $changes, ?ParkingRegistration $registration): array
    {
        if ($registration === null) {
            throw ValidationException::withMessages([
                'parking_registration_id' => __('ticket_change_request.validation.registration_required'),
            ]);
        }

        $normalized = [];

        foreach (TicketChangeRequest::AUTO_APPLY_FIELDS as $field) {
            if (! array_key_exists($field, $changes)) {
                continue;
            }

            $value = $changes[$field];
            if ($value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }

            if ($field === 'vehicle_type') {
                $value = in_array($value, ['car', 'coach'], true) ? $value : null;
                if ($value === null) {
                    continue;
                }
            }

            if ($field === 'email') {
                $email = strtolower(trim((string) $value));
                Validator::make(
                    ['email' => $email],
                    ['email' => ['required', 'email', 'max:255', new PersonalEmail]],
                )->validate();
                $value = $email;
            }

            if ($field === 'name' || $field === 'contact_number') {
                $value = trim((string) $value);
            }

            if ($field === 'vehicle_registration') {
                $vehicleType = (string) ($changes['vehicle_type'] ?? $registration->vehicle_type ?? 'car');
                $value = VehicleRegistrationNormalizer::normalize((string) $value, $vehicleType);
            }

            $current = $registration->{$field};
            if (is_string($current) && is_string($value) && strcasecmp(trim($current), trim($value)) === 0) {
                continue;
            }

            $normalized[$field] = $value;
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'changes' => __('ticket_change_request.validation.changes_required'),
            ]);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $addition
     * @return array{
     *     name: string,
     *     contact_number: string,
     *     email: string,
     *     vehicle_type: string,
     *     vehicle_registration: ?string,
     *     days: list<string>,
     *     elderly_infirm_parking: bool
     * }
     */
    private function normalizeAddition(array $addition): array
    {
        $validated = Validator::make($addition, [
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255', new PersonalEmail],
            'vehicle_type' => ['required', Rule::in(['car', 'coach'])],
            'vehicle_registration' => ['nullable', 'string', 'max:64'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => [Rule::in(['Friday', 'Saturday', 'Sunday'])],
            'elderly_infirm_parking' => ['sometimes', 'boolean'],
        ])->validate();

        $vehicleType = (string) $validated['vehicle_type'];
        $vehicleReg = VehicleRegistrationNormalizer::normalize(
            isset($validated['vehicle_registration']) ? (string) $validated['vehicle_registration'] : null,
            $vehicleType,
        );

        if ($vehicleType === 'car' && ($vehicleReg === null || $vehicleReg === '')) {
            throw ValidationException::withMessages([
                'addition.vehicle_registration' => __('ticket_change_request.validation.vehicle_registration_required'),
            ]);
        }

        return [
            'name' => trim((string) $validated['name']),
            'contact_number' => trim((string) $validated['contact_number']),
            'email' => strtolower(trim((string) $validated['email'])),
            'vehicle_type' => $vehicleType,
            'vehicle_registration' => $vehicleReg,
            'days' => array_values($validated['days']),
            'elderly_infirm_parking' => (bool) ($validated['elderly_infirm_parking'] ?? false),
        ];
    }

    private function defaultNotes(string $type, ?ParkingRegistration $registration): string
    {
        $ticket = $registration?->ticketNumber() ?? '';

        return match ($type) {
            TicketChangeRequest::TYPE_FIELD_UPDATE => 'Field update request'.($ticket !== '' ? ' for ticket '.$ticket : ''),
            TicketChangeRequest::TYPE_CANCELLATION => 'Cancellation request'.($ticket !== '' ? ' for ticket '.$ticket : ''),
            TicketChangeRequest::TYPE_ADDITION => 'Addition request',
            TicketChangeRequest::TYPE_EMAIL_REQUEST => 'Email request',
            default => 'Ticket change request',
        };
    }
}
