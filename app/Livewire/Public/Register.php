<?php

namespace App\Livewire\Public;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use App\Services\ParkingRegistrationDuplicateSignals;
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

    #[Computed]
    public function duplicateVehicleRegistrationConflict(): ?ParkingRegistration
    {
        $signals = app(ParkingRegistrationDuplicateSignals::class);
        $norm = $signals->normalizeVehicleRegistration($this->vehicleReg);

        return $signals->findActiveByNormalizedVehicleReg($norm);
    }

    #[Computed]
    public function duplicateEmailExistingRegistration(): ?ParkingRegistration
    {
        $email = trim($this->email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $signals = app(ParkingRegistrationDuplicateSignals::class);
        $found = $signals->findActiveByNormalizedEmail($email);
        if ($found === null) {
            return null;
        }

        $vehicleConflict = $this->duplicateVehicleRegistrationConflict;
        if ($vehicleConflict !== null && $vehicleConflict->is($found)) {
            return null;
        }

        return $found;
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

        $this->validate($rules);

        $congregation = $this->resolvedCongregation;
        if (! $congregation) {
            $this->addError('congregationCode', __('register.invalid_congregation_code'));

            return;
        }

        $formattedReg = $this->vehicleType === 'car' && trim($this->vehicleReg) !== ''
            ? strtoupper(str_replace(' ', '', trim($this->vehicleReg)))
            : null;

        // Race-safe quota enforcement: lock the survey response row, then
        // recount registrations inside the same transaction before inserting.
        // Quota violations short-circuit with a [field, message] tuple; the
        // transaction commits with zero changes (idempotent), no INSERT having
        // run. Coach sharing details are NOT re-collected here — the parking
        // survey is the single source of truth for those.
        //
        // Pool model = ADDITIVE: the survey's car_park_tickets_count is the
        // limit for STANDARD (non-disabled) cars only, and disabled_parking_count
        // is an independent pool for elderly/infirm cars. A disabled
        // registration never consumes a standard slot and vice versa.
        $violation = DB::transaction(function () use ($congregation, $formattedReg): ?array {
            $resp = CongregationNumbersResponse::query()
                ->where('congregation_id', $congregation->id)
                ->lockForUpdate()
                ->first();

            if ($resp === null) {
                return ['congregationCode', __('register.quota_no_survey')];
            }

            $standardCarUsed = ParkingRegistration::query()
                ->where('congregation', $congregation->name)
                ->where('vehicle_type', 'car')
                ->where('elderly_infirm_parking', false)
                ->count();

            $disabledUsed = ParkingRegistration::query()
                ->where('congregation', $congregation->name)
                ->where('vehicle_type', 'car')
                ->where('elderly_infirm_parking', true)
                ->count();

            $coachExists = ParkingRegistration::query()
                ->where('congregation', $congregation->name)
                ->where('vehicle_type', 'coach')
                ->exists();

            if ($this->vehicleType === 'car') {
                if ($this->elderlyInfirmParking === '1') {
                    $disabledLimit = $resp->disabled_parking_required
                        ? (int) ($resp->disabled_parking_count ?? 0)
                        : 0;

                    if ($disabledLimit <= 0) {
                        return ['elderlyInfirmParking', __('register.quota_disabled_not_requested', [
                            'congregation' => $congregation->name,
                        ])];
                    }

                    if ($disabledUsed >= $disabledLimit) {
                        return ['elderlyInfirmParking', __('register.quota_disabled_full', [
                            'limit' => $disabledLimit,
                            'congregation' => $congregation->name,
                        ])];
                    }
                } else {
                    $standardLimit = (int) $resp->car_park_tickets_count;

                    if ($standardCarUsed >= $standardLimit) {
                        return ['congregationCode', __('register.quota_car_full', [
                            'limit' => $standardLimit,
                            'congregation' => $congregation->name,
                        ])];
                    }
                }
            } else { // coach
                if (! $resp->organizes_coach) {
                    return ['vehicleType', __('register.quota_coach_not_organised')];
                }
                if ($coachExists) {
                    return ['vehicleType', __('register.quota_coach_taken')];
                }
            }

            $duplicateSignals = app(ParkingRegistrationDuplicateSignals::class);
            if ($formattedReg !== null) {
                $existingByReg = $duplicateSignals->findActiveByNormalizedVehicleReg($formattedReg);
                if ($existingByReg !== null) {
                    return ['vehicleReg', __('register.duplicate_vehicle_registration', [
                        'name' => $existingByReg->name,
                        'congregation' => $existingByReg->congregation ?: '—',
                    ])];
                }
            }

            ParkingRegistration::create([
                'name' => $this->name,
                'congregation' => $congregation->name,
                'contact_number' => $this->contactNumber,
                'vehicle_registration' => $formattedReg,
                'days' => $this->days,
                'email' => $this->email,
                'vehicle_type' => $this->vehicleType,
                // Survey is the source of truth for coach sharing — do not duplicate.
                'sharing_with_other_congregations' => false,
                'sharing_congregations_notes' => null,
                'elderly_infirm_parking' => $this->vehicleType === 'car'
                    ? filter_var($this->elderlyInfirmParking, FILTER_VALIDATE_BOOLEAN)
                    : false,
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
