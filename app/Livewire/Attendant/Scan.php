<?php

declare(strict_types=1);

namespace App\Livewire\Attendant;

use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Scan extends Component
{
    public string $uuid = '';

    public string $vehicleReg = '';

    public string $contactNumber = '';

    public string $name = '';

    public string $email = '';

    /** @var list<string> */
    public array $days = [];

    public bool $elderlyInfirmParking = false;

    public string $notes = '';

    public string $step = 'scan';

    public ?Congregation $scannedCongregation = null;

    public ?string $lastScanResult = null;

    public string $lastScanMessage = '';

    public ?ParkingPass $lastScanPass = null;

    public ?ParkingRegistration $foundRegistration = null;

    public ?ParkingPass $existingParkedPass = null;

    public ?ParkingRegistration $scannedRegistration = null;

    public bool $quickCheckIn = false;

    public bool $walkInMode = false;

    public ?int $selectedCongregationId = null;

    #[Layout('components.layouts.public')]
    public function mount($code = null, ?ParkingRegistration $registration = null): void
    {
        if (request()->routeIs('attendant.scan.walk-in') || request()->query('mode') === 'walk-in') {
            $this->startWalkInMode();

            return;
        }

        if ($registration !== null) {
            $this->scanRegistration($registration);

            return;
        }

        if ($code) {
            $this->uuid = (string) $code;
            $this->scan();
        } elseif (request()->has('code')) {
            $this->uuid = (string) request()->query('code');
            $this->scan();
        }
    }

    #[Computed]
    public function congregations(): Collection
    {
        return Congregation::query()
            ->with('carPark')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        $effectiveCarPark = $this->step === 'confirm' ? $this->resolveEffectiveCarPark() : null;

        return view('livewire.attendant.scan', [
            'effectiveCarPark' => $effectiveCarPark,
        ]);
    }

    public function scan(): void
    {
        $this->uuid = trim($this->uuid);

        if ($this->uuid === '') {
            return;
        }

        if (filter_var($this->uuid, FILTER_VALIDATE_URL)) {
            try {
                $path = (string) parse_url($this->uuid, PHP_URL_PATH);
                if (preg_match('#registrations/(\d+)/print#', $path, $matches)) {
                    $registration = ParkingRegistration::query()->find((int) $matches[1]);
                    if ($registration !== null) {
                        $this->scanRegistration($registration);

                        return;
                    }
                }

                $segments = explode('/', trim($path, '/'));
                $lastSegment = end($segments);
                if (is_string($lastSegment) && $lastSegment !== '') {
                    $this->uuid = $lastSegment;
                }
            } catch (\Exception) {
                // Keep original value if parsing fails
            }
        }

        if (preg_match('#^ticket[/-](\d+)$#i', $this->uuid, $matches)) {
            $registration = ParkingRegistration::query()->find((int) $matches[1]);
            if ($registration !== null) {
                $this->scanRegistration($registration);

                return;
            }
        }

        $congregation = Congregation::query()
            ->where('uuid', $this->uuid)
            ->with('carPark')
            ->first();

        if ($congregation === null) {
            $this->setResult('error', 'INVALID PASS', 'This code does not match any congregation.');
            $this->reset('uuid');

            return;
        }

        $this->scannedCongregation = $congregation;
        $this->step = 'confirm';
        $this->quickCheckIn = false;
        $this->walkInMode = false;
        $this->reset('vehicleReg', 'elderlyInfirmParking', 'notes', 'existingParkedPass', 'scannedRegistration');
    }

    public function scanRegistration(ParkingRegistration $registration): void
    {
        $registration->load('carPark');

        if ($registration->is_circuit_overseer) {
            $this->setResult('error', 'INVALID TICKET', 'Circuit overseer tickets cannot be scanned this way. Use walk-in check-in.');
            $this->reset('uuid');

            return;
        }

        $congregation = Congregation::query()
            ->where('name', $registration->congregation)
            ->with('carPark')
            ->first();

        if ($congregation === null) {
            $this->setResult('error', 'INVALID TICKET', 'Congregation not found for this registration.');
            $this->reset('uuid');

            return;
        }

        $this->scannedRegistration = $registration;
        $this->scannedCongregation = $congregation;
        $this->foundRegistration = $registration;
        $this->vehicleReg = strtoupper(str_replace(' ', '', (string) $registration->vehicle_registration));
        $this->contactNumber = (string) $registration->contact_number;
        $this->name = (string) $registration->name;
        $this->email = (string) ($registration->email ?? '');
        $this->days = $registration->days ?? [];
        $this->elderlyInfirmParking = (bool) ($registration->elderly_infirm_parking ?? false);
        $this->notes = '';
        $this->step = 'confirm';
        $this->walkInMode = false;
        $this->quickCheckIn = $this->canQuickCheckIn();
        $this->checkExistingParkedPass();
    }

    public function toggleDay(string $day): void
    {
        if (in_array($day, $this->days, true)) {
            $this->days = array_values(array_diff($this->days, [$day]));
        } else {
            $this->days[] = $day;
        }
    }

    public function updatedVehicleReg(): void
    {
        $this->vehicleReg = strtoupper(str_replace(' ', '', trim($this->vehicleReg)));

        if (strlen($this->vehicleReg) > 2) {
            $query = ParkingRegistration::query()->where('vehicle_registration', $this->vehicleReg);
            if ($this->scannedCongregation) {
                $reg = (clone $query)->where('congregation', $this->scannedCongregation->name)->first()
                    ?? $query->first();
            } else {
                $reg = $query->first();
            }

            if ($reg) {
                $this->foundRegistration = $reg;
                $this->contactNumber = (string) $reg->contact_number;
                $this->name = (string) $reg->name;
                $this->email = (string) ($reg->email ?? '');
                $this->days = $reg->days ?? [];
                $this->elderlyInfirmParking = (bool) ($reg->elderly_infirm_parking ?? false);
            } else {
                $this->foundRegistration = null;
            }
        } else {
            $this->foundRegistration = null;
            $this->existingParkedPass = null;
        }

        $this->checkExistingParkedPass();
    }

    public function updatedSelectedCongregationId(): void
    {
        if ($this->selectedCongregationId) {
            $this->scannedCongregation = Congregation::query()
                ->with('carPark')
                ->find($this->selectedCongregationId);
        } else {
            $this->scannedCongregation = null;
        }

        $this->foundRegistration = null;
        $this->existingParkedPass = null;
    }

    public function confirm(): void
    {
        if ($this->walkInMode) {
            $this->validate([
                'selectedCongregationId' => 'required|integer|exists:congregations,id',
            ]);

            $this->scannedCongregation = Congregation::query()
                ->with('carPark')
                ->find($this->selectedCongregationId);
        }

        if ($this->scannedCongregation === null) {
            $this->cancel();

            return;
        }

        $carPark = $this->resolveEffectiveCarPark();
        if ($carPark === null) {
            $this->setResult('error', 'NO CAR PARK', 'No car park assigned. Assign the congregation or this individual to a car park in Admin first.');

            return;
        }

        $this->validate([
            'vehicleReg' => 'required|string|min:2',
            'contactNumber' => 'required|string|min:6',
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'days' => 'nullable|array',
            'notes' => 'nullable|string|max:255',
        ]);

        $formattedReg = strtoupper(str_replace(' ', '', trim($this->vehicleReg)));

        if (strlen($formattedReg) >= 2) {
            $alreadyParked = ParkingPass::query()
                ->where('status', 'parked')
                ->get()
                ->contains(fn (ParkingPass $p) => strtoupper(str_replace(' ', '', (string) ($p->vehicle_reg ?? ''))) === $formattedReg);

            if ($alreadyParked) {
                $this->setResult('error', 'ALREADY PARKED', 'This vehicle is already clocked in and cannot be registered again.');

                return;
            }
        }

        $currentOccupancy = ParkingPass::query()
            ->where('status', 'parked')
            ->where(function ($query) use ($carPark) {
                $query->where('car_park_id', $carPark->id)
                    ->orWhere(function ($q) use ($carPark) {
                        $q->whereNull('car_park_id')
                            ->whereHas('congregation', fn ($c) => $c->where('car_park_id', $carPark->id));
                    });
            })
            ->count();

        if ($currentOccupancy >= $carPark->capacity) {
            $this->setResult('error', 'CAR PARK FULL', 'The '.$carPark->name.' is at capacity ('.$carPark->capacity.').');

            return;
        }

        if ($currentOccupancy >= ($carPark->capacity * 0.9)) {
            Flux::toast('Warning: '.$carPark->name.' is almost full!', variant: 'warning');
        }

        try {
            $pass = ParkingPass::query()->create([
                'congregation_id' => $this->scannedCongregation->id,
                'car_park_id' => $carPark->id,
                'status' => 'parked',
                'vehicle_reg' => $formattedReg,
                'contact_number' => $this->contactNumber,
                'name' => $this->name,
                'email' => $this->email,
                'days' => $this->days,
                'elderly_infirm_parking' => $this->elderlyInfirmParking,
                'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
                'scanned_at' => now(),
                'scanned_by_user_id' => auth()->id(),
            ]);

            ParkingRegistration::query()->updateOrCreate(
                ['vehicle_registration' => $formattedReg],
                [
                    'congregation' => $this->scannedCongregation->name,
                    'name' => $this->name ?? '',
                    'contact_number' => $this->contactNumber,
                    'email' => $this->email,
                    'days' => $this->days,
                ]
            );

            $this->setResult('success', 'ACCESS GRANTED', $this->scannedCongregation->name.' -> '.$carPark->name);

            $pass->load('congregation', 'carPark');
            $this->lastScanPass = $pass;

            $this->resetAfterSuccessfulCheckIn();
        } catch (\Exception $e) {
            Flux::toast('Error: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function clockOut(int $passId): void
    {
        if (! auth()->check()) {
            return;
        }

        $pass = ParkingPass::query()->where('id', $passId)->where('status', 'parked')->first();

        if ($pass === null) {
            Flux::toast('Pass not found or already clocked out.', variant: 'warning');
            $this->existingParkedPass = null;

            return;
        }

        $pass->update([
            'status' => 'left',
            'left_at' => now(),
        ]);

        $this->existingParkedPass = null;
        Flux::toast('Vehicle '.($pass->vehicle_reg ?? '').' clocked out.');
    }

    public function checkInAnotherCar(): void
    {
        $this->reset('vehicleReg', 'contactNumber', 'name', 'email', 'days', 'elderlyInfirmParking', 'notes', 'foundRegistration', 'existingParkedPass', 'scannedRegistration', 'quickCheckIn');
    }

    public function cancel(): void
    {
        $walkIn = $this->walkInMode;

        $this->reset(
            'uuid',
            'step',
            'scannedCongregation',
            'vehicleReg',
            'contactNumber',
            'name',
            'email',
            'days',
            'elderlyInfirmParking',
            'notes',
            'foundRegistration',
            'existingParkedPass',
            'scannedRegistration',
            'quickCheckIn',
            'selectedCongregationId',
            'lastScanResult',
            'lastScanMessage',
        );

        if ($walkIn) {
            $this->walkInMode = true;
            $this->step = 'confirm';
        } else {
            $this->walkInMode = false;
            $this->step = 'scan';
        }
    }

    protected function startWalkInMode(): void
    {
        $this->walkInMode = true;
        $this->quickCheckIn = false;
        $this->step = 'confirm';
        $this->scannedCongregation = null;
        $this->selectedCongregationId = null;
    }

    protected function canQuickCheckIn(): bool
    {
        return strlen($this->vehicleReg) >= 2
            && strlen($this->contactNumber) >= 6;
    }

    protected function checkExistingParkedPass(): void
    {
        if (strlen($this->vehicleReg) <= 2 || ! auth()->check() || $this->scannedCongregation === null) {
            $this->existingParkedPass = null;

            return;
        }

        $formattedReg = strtoupper(str_replace(' ', '', $this->vehicleReg));
        $this->existingParkedPass = ParkingPass::query()
            ->where('congregation_id', $this->scannedCongregation->id)
            ->where('status', 'parked')
            ->whereRaw('REPLACE(UPPER(vehicle_reg), " ", "") = ?', [$formattedReg])
            ->first();
    }

    protected function resolveEffectiveCarPark(): ?CarPark
    {
        if ($this->foundRegistration) {
            $reg = ParkingRegistration::query()->find($this->foundRegistration->id);
            if ($reg?->car_park_id) {
                $park = CarPark::query()->find($reg->car_park_id);
                if ($park) {
                    return $park;
                }
            }
        }

        return $this->scannedCongregation?->carPark;
    }

    protected function resetAfterSuccessfulCheckIn(): void
    {
        $walkIn = $this->walkInMode;

        $this->reset(
            'uuid',
            'scannedCongregation',
            'vehicleReg',
            'contactNumber',
            'name',
            'email',
            'days',
            'elderlyInfirmParking',
            'notes',
            'foundRegistration',
            'existingParkedPass',
            'scannedRegistration',
            'quickCheckIn',
            'selectedCongregationId',
        );

        if ($walkIn) {
            $this->walkInMode = true;
            $this->step = 'confirm';
        } else {
            $this->walkInMode = false;
            $this->step = 'scan';
        }
    }

    protected function setResult(string $type, string $title, string $message): void
    {
        $this->lastScanResult = $type;
        $this->lastScanMessage = $title;

        if ($type === 'success') {
            Flux::toast('Pass Scanned Successfully');
        }
        if ($type === 'error') {
            Flux::toast('Invalid Pass', variant: 'danger');
        }
        if ($type === 'warning') {
            Flux::toast('Already Scanned', variant: 'warning');
        }
    }
}
