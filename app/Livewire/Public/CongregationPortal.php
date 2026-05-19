<?php

namespace App\Livewire\Public;

use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Services\CongregationPortalAuth;
use App\Services\ParkingRegistrationQuotaValidator;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class CongregationPortal extends Component
{
    public string $congregationCode = '';

    public string $password = '';

    public bool $editModalOpen = false;

    public ?int $editingId = null;

    public string $vehicleType = 'car';

    public string $vehicleReg = '';

    /** @var array<int, string> */
    public array $days = [];

    public string $elderlyInfirmParking = '0';

    /** @var list<string> */
    protected static array $allDays = ['Friday', 'Saturday', 'Sunday'];

    #[Computed]
    public function congregation(): ?Congregation
    {
        return app(CongregationPortalAuth::class)->authenticatedCongregation();
    }

    #[Computed]
    public function isAuthenticated(): bool
    {
        return $this->congregation !== null;
    }

    #[Computed]
    public function portalConfigured(): bool
    {
        return app(CongregationPortalAuth::class)->passwordIsConfigured();
    }

    /**
     * @return array{
     *     has_survey: bool,
     *     car_tickets: int,
     *     disabled_spaces: int,
     *     disabled_required: bool,
     *     organizes_coach: bool,
     *     coach_size: string|null,
     *     filled_cars: int,
     *     filled_disabled: int,
     *     filled_coach: int,
     * }
     */
    #[Computed]
    public function surveySummary(): array
    {
        $congregation = $this->congregation;
        if ($congregation === null) {
            return [
                'has_survey' => false,
                'car_tickets' => 0,
                'disabled_spaces' => 0,
                'disabled_required' => false,
                'organizes_coach' => false,
                'coach_size' => null,
                'filled_cars' => 0,
                'filled_disabled' => 0,
                'filled_coach' => 0,
            ];
        }

        $resp = $congregation->numbersResponse;
        $label = trim((string) $congregation->name);
        $counts = app(ParkingRegistrationQuotaValidator::class)->countsForCongregationLabel($label);
        $disabledRequired = (bool) ($resp?->disabled_parking_required ?? false);

        // When the survey did not request separate disabled spaces, all cars share the ticket cap.
        $filledCars = $disabledRequired
            ? $counts['standard_car_used']
            : ($counts['standard_car_used'] + $counts['disabled_used']);

        return [
            'has_survey' => $resp !== null,
            'car_tickets' => (int) ($resp?->car_park_tickets_count ?? 0),
            'disabled_spaces' => $resp !== null && $disabledRequired
                ? (int) ($resp->disabled_parking_count ?? 0)
                : 0,
            'disabled_required' => $disabledRequired,
            'organizes_coach' => (bool) ($resp?->organizes_coach ?? false),
            'coach_size' => $resp?->coach_size,
            'filled_cars' => $filledCars,
            'filled_disabled' => $counts['disabled_used'],
            'filled_coach' => $counts['coach_exists'] ? 1 : 0,
        ];
    }

    public function login(): void
    {
        if (! $this->portalConfigured) {
            $this->addError('congregationCode', __('congregation_portal.portal_not_configured'));

            return;
        }

        $this->validate([
            'congregationCode' => 'required|string',
            'password' => 'required|string',
        ]);

        $auth = app(CongregationPortalAuth::class);
        $congregation = $auth->attempt(trim($this->congregationCode), $this->password);

        if ($congregation === null) {
            $this->addError('congregationCode', __('congregation_portal.invalid_credentials'));

            return;
        }

        $auth->login($congregation);
        $this->reset('congregationCode', 'password');
        unset($this->congregation, $this->isAuthenticated, $this->surveySummary);
    }

    public function logout(): void
    {
        app(CongregationPortalAuth::class)->logout();
        unset($this->congregation, $this->isAuthenticated, $this->surveySummary);
    }

    public function openEdit(int $id): void
    {
        $registration = $this->findOwnedRegistration($id);
        if ($registration === null) {
            return;
        }

        $this->editingId = $registration->id;
        $this->vehicleType = $registration->vehicle_type ?? 'car';
        $this->vehicleReg = $registration->vehicle_registration ?? '';
        $this->days = $registration->days ?? [];
        $this->elderlyInfirmParking = $registration->elderly_infirm_parking ? '1' : '0';
        $this->editModalOpen = true;
    }

    public function closeEdit(): void
    {
        $this->editModalOpen = false;
        $this->editingId = null;
        $this->reset('vehicleType', 'vehicleReg', 'days', 'elderlyInfirmParking');
    }

    public function saveEdit(): void
    {
        $congregation = $this->congregation;
        if ($congregation === null || $this->editingId === null) {
            return;
        }

        $registration = $this->findOwnedRegistration($this->editingId);
        if ($registration === null) {
            return;
        }

        if ($this->vehicleType === 'car') {
            $this->elderlyInfirmParking = $this->normalizeElderlyInfirmParking($this->elderlyInfirmParking);
        } else {
            $this->elderlyInfirmParking = '0';
        }

        $rules = [
            'vehicleType' => 'required|in:car,coach',
            'days' => 'required|array|min:1',
            'days.*' => 'in:Friday,Saturday,Sunday',
        ];

        if ($this->vehicleType === 'car') {
            $rules['vehicleReg'] = 'required|string|max:20';
            $rules['elderlyInfirmParking'] = 'required|in:0,1';
        } else {
            $rules['vehicleReg'] = 'nullable|string|max:20';
        }

        $this->validate($rules);

        $formattedReg = $this->vehicleType === 'car' && trim($this->vehicleReg) !== ''
            ? strtoupper(str_replace(' ', '', trim($this->vehicleReg)))
            : null;

        $elderlyInfirm = $this->vehicleType === 'car'
            && filter_var($this->elderlyInfirmParking, FILTER_VALIDATE_BOOLEAN);

        $validator = app(ParkingRegistrationQuotaValidator::class);
        $violation = $validator->validateRegistration(
            $congregation,
            $this->vehicleType,
            $elderlyInfirm,
            $formattedReg,
            $registration->id,
            $registration,
        );

        if ($violation !== null) {
            [$field, $message] = $violation;
            $this->addError($field, $message);

            return;
        }

        $registration->update([
            'vehicle_type' => $this->vehicleType,
            'vehicle_registration' => $formattedReg,
            'days' => $this->days,
            'elderly_infirm_parking' => $elderlyInfirm,
        ]);

        $this->closeEdit();
        unset($this->surveySummary);

        try {
            Flux::toast(__('congregation_portal.updated'));
        } catch (\Throwable) {
            session()->flash('status', __('congregation_portal.updated'));
        }
    }

    public function render()
    {
        $registrations = collect();

        if ($this->isAuthenticated) {
            $label = trim((string) $this->congregation->name);
            $registrations = ParkingRegistration::query()
                ->whereRaw('TRIM(congregation) = ?', [$label])
                ->where('is_circuit_overseer', false)
                ->orderBy('vehicle_type')
                ->orderBy('vehicle_registration')
                ->get();
        }

        return view('livewire.public.congregation-portal', [
            'registrations' => $registrations,
        ]);
    }

    private function findOwnedRegistration(int $id): ?ParkingRegistration
    {
        $congregation = $this->congregation;
        if ($congregation === null) {
            return null;
        }

        $label = trim((string) $congregation->name);

        return ParkingRegistration::query()
            ->whereKey($id)
            ->whereRaw('TRIM(congregation) = ?', [$label])
            ->where('is_circuit_overseer', false)
            ->first();
    }

    private function normalizeElderlyInfirmParking(mixed $value): string
    {
        if ($value === true || $value === 1 || $value === '1') {
            return '1';
        }

        return '0';
    }
}
