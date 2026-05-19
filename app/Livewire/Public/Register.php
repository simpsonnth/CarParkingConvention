<?php

namespace App\Livewire\Public;

use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Services\ParkingRegistrationDuplicateSignals;
use App\Services\ParkingRegistrationQuotaValidator;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class Register extends Component
{
    public string $vehicleType = 'car'; // 'car' or 'coach'

    /** Congregation UUID typed by the user. */
    public string $congregationCode = '';

    public string $name = '';

    public string $contactNumber = '';

    public string $vehicleReg = '';

    /** @var array<int,string> */
    public array $days = []; // ['Friday', 'Saturday', 'Sunday']

    public string $email = '';

    /** Do you need parking for Elderly and Infirm? Bound as a string '0'/'1'. */
    public string $elderlyInfirmParking = '0';

    /**
     * True when the congregation has not yet appointed a coach captain and
     * the contact details on this submission belong to the congregation
     * secretary acting as a temporary point of contact. Coach-only.
     */
    public bool $coachCaptainToBeAssigned = false;

    public bool $registered = false;

    /** @var list<string> */
    protected static array $allDays = ['Friday', 'Saturday', 'Sunday'];

    #[Computed]
    public function resolvedCongregation(): ?Congregation
    {
        $code = trim($this->congregationCode);
        if ($code === '') {
            return null;
        }

        return Congregation::where('uuid', $code)->first();
    }

    /**
     * When a valid congregation code is entered, determines whether any survey slot
     * remains (standard car, disabled car, or coach). Used to hide the rest of the form.
     *
     * @return array{hide_remaining_fields: bool, no_survey: bool, allocation_full: bool}
     */
    #[Computed]
    public function congregationQuotaGate(): array
    {
        $congregation = $this->resolvedCongregation;
        if ($congregation === null) {
            return [
                'hide_remaining_fields' => false,
                'no_survey' => false,
                'allocation_full' => false,
            ];
        }

        return app(ParkingRegistrationQuotaValidator::class)->congregationQuotaGate($congregation);
    }

    #[Computed]
    public function duplicateVehicleRegistrationConflict(): ?ParkingRegistration
    {
        $signals = app(ParkingRegistrationDuplicateSignals::class);
        $norm = $signals->normalizeVehicleRegistration($this->vehicleReg);

        return $signals->findActiveByNormalizedVehicleReg($norm);
    }

    /**
     * Keep the "coach captain to be assigned" flag scoped to coach
     * submissions only. If a user toggles to car after ticking the box,
     * the flag must not silently persist into the row.
     */
    public function updatedVehicleType(string $value): void
    {
        if ($value !== 'coach') {
            $this->coachCaptainToBeAssigned = false;
        }
    }

    public function toggleAllDays(): void
    {
        if (count($this->days) === count(self::$allDays)) {
            $this->days = [];
        } else {
            $this->days = self::$allDays;
        }
    }

    public function render()
    {
        return view('livewire.public.register');
    }

    public function register(): void
    {
        $rules = [
            'vehicleType' => 'required|in:car,coach',
            'congregationCode' => 'required|string|exists:congregations,uuid',
            'name' => 'required|string|max:255',
            'contactNumber' => 'required|string|max:255',
            'days' => 'required|array|min:1',
            'days.*' => 'in:Friday,Saturday,Sunday',
            'email' => 'required|email|max:255',
        ];

        if ($this->vehicleType === 'car') {
            $rules['vehicleReg'] = 'required|string|max:20';
            $rules['elderlyInfirmParking'] = 'required|in:0,1';
        } else {
            $rules['vehicleReg'] = 'nullable|string|max:20';
            $rules['elderlyInfirmParking'] = 'nullable|in:0,1';
        }

        $rules['coachCaptainToBeAssigned'] = 'boolean';

        $this->validate($rules);

        // Defence in depth: the flag is meaningless outside coach submissions.
        $coachCaptainTba = $this->vehicleType === 'coach' && $this->coachCaptainToBeAssigned;

        $congregation = $this->resolvedCongregation;
        if (! $congregation) {
            $this->addError('congregationCode', __('register.invalid_congregation_code'));

            return;
        }

        $formattedReg = $this->vehicleType === 'car' && trim($this->vehicleReg) !== ''
            ? strtoupper(str_replace(' ', '', trim($this->vehicleReg)))
            : null;

        $congregationLabel = trim((string) $congregation->name);
        $elderlyInfirm = $this->vehicleType === 'car'
            && filter_var($this->elderlyInfirmParking, FILTER_VALIDATE_BOOLEAN);

        $duplicateSignals = app(ParkingRegistrationDuplicateSignals::class);
        if ($formattedReg !== null) {
            $existingByReg = $duplicateSignals->findActiveByNormalizedVehicleReg($formattedReg);
            if ($existingByReg !== null) {
                $this->addError('vehicleReg', __('register.duplicate_vehicle_registration', [
                    'name' => $existingByReg->name,
                    'congregation' => $existingByReg->congregation ?: '—',
                ]));

                return;
            }
        }

        $validator = app(ParkingRegistrationQuotaValidator::class);
        $violation = DB::transaction(function () use (
            $congregation,
            $formattedReg,
            $congregationLabel,
            $coachCaptainTba,
            $elderlyInfirm,
            $validator,
        ): ?array {
            $quotaViolation = $validator->validateRegistration(
                $congregation,
                $this->vehicleType,
                $elderlyInfirm,
                $formattedReg,
            );

            if ($quotaViolation !== null) {
                return $quotaViolation;
            }

            ParkingRegistration::create([
                'name' => $this->name,
                'congregation' => $congregationLabel,
                'contact_number' => $this->contactNumber,
                'vehicle_registration' => $formattedReg,
                'days' => $this->days,
                'email' => $this->email,
                'vehicle_type' => $this->vehicleType,
                'sharing_with_other_congregations' => false,
                'sharing_congregations_notes' => null,
                'elderly_infirm_parking' => $elderlyInfirm,
                'coach_captain_to_be_assigned' => $coachCaptainTba,
            ]);

            return null;
        });

        if ($violation !== null) {
            [$field, $message] = $violation;
            $this->addError($field, $message);

            return;
        }

        $this->registered = true;

        try {
            Flux::toast(__('register.registration_complete'));
        } catch (\Throwable) {
            session()->flash('status', __('register.registration_complete'));
        }
    }
}
