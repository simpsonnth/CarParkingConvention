<?php

namespace App\Livewire\Attendant;

use App\Models\CarPark;
use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\Layout;

class Scan extends Component
{
    public $uuid = '';
    public $vehicleReg = '';
    public $contactNumber = '';
    public $name = '';
    public $email = '';
    public $days = [];
    public $elderlyInfirmParking = false;
    public $notes = '';

    public $step = 'scan'; // 'scan', 'confirm'
    public $scannedCongregation = null;


    public $lastScanResult = null; // 'success', 'error', 'warning'
    public $lastScanMessage = '';
    public $lastScanPass = null; // Used for "Previous Scan" info

    #[Layout('components.layouts.public')]
    public function mount($code = null)
    {
        if ($code) {
            $this->uuid = $code;
            $this->scan();
        } elseif (request()->has('code')) {
            $this->uuid = request()->query('code');
            $this->scan();
        }
    }

    public function render()
    {
        $effectiveCarPark = $this->step === 'confirm' ? $this->resolveEffectiveCarPark() : null;

        return view('livewire.attendant.scan', [
            'effectiveCarPark' => $effectiveCarPark,
        ]);
    }

    public function scan()
    {
        $this->uuid = trim($this->uuid);

        if (empty($this->uuid)) {
            return;
        }

        // If UUID is a full URL, extract the last segment
        if (filter_var($this->uuid, FILTER_VALIDATE_URL)) {
            try {
                $path = parse_url($this->uuid, PHP_URL_PATH);
                $segments = explode('/', trim($path, '/'));
                // Assuming URL structure like /scan/{uuid} or /admin/congregations/{uuid}/print...
                // But usually the public scan link is /scan/{uuid}
                $this->uuid = end($segments);
            } catch (\Exception $e) {
                // Keep original value if parsing fails
            }
        }

        // Find the Congregation by the scanned UUID
        $congregation = \App\Models\Congregation::where('uuid', $this->uuid)->with('carPark')->first();

        if (!$congregation) {
            $this->setResult('error', 'INVALID PASS', 'This code does not match any congregation.');
            $this->reset('uuid');
            return;
        }

        $this->scannedCongregation = $congregation;
        $this->step = 'confirm';
        $this->reset('vehicleReg', 'elderlyInfirmParking', 'notes', 'existingParkedPass');
    }

    public $foundRegistration = null;

    /** Single parked pass when the typed reg matches an already-parked vehicle (for display only). */
    public $existingParkedPass = null;

    public function toggleDay($day)
    {
        if (in_array($day, $this->days)) {
            $this->days = array_values(array_diff($this->days, [$day]));
        } else {
            $this->days[] = $day;
        }
    }

    public function updatedVehicleReg()
    {
        $this->vehicleReg = strtoupper(str_replace(' ', '', trim($this->vehicleReg)));
        // ... (rest of function)

        if (strlen($this->vehicleReg) > 2) {
            $query = ParkingRegistration::where('vehicle_registration', $this->vehicleReg);
            if ($this->scannedCongregation) {
                $reg = (clone $query)->where('congregation', $this->scannedCongregation->name)->first()
                    ?? $query->first();
            } else {
                $reg = $query->first();
            }

            if ($reg) {
                $this->foundRegistration = $reg;
                $this->contactNumber = $reg->contact_number;
                $this->name = $reg->name;
                $this->email = $reg->email ?? '';
                $this->days = $reg->days ?? [];
                $this->elderlyInfirmParking = (bool) ($reg->elderly_infirm_parking ?? false);
            } else {
                $this->foundRegistration = null;
                // Keep contact number if user typed it, or reset? 
                // Better to not reset if they typed it manually, but if it was autofilled from previous check, maybe?
                // For now, let's leave it.
            }
        } else {
            $this->foundRegistration = null;
            $this->existingParkedPass = null;
        }

        if (strlen($this->vehicleReg) > 2 && auth()->check() && $this->scannedCongregation) {
            $formattedReg = strtoupper(str_replace(' ', '', $this->vehicleReg));
            $pass = ParkingPass::where('congregation_id', $this->scannedCongregation->id)
                ->where('status', 'parked')
                ->whereRaw('REPLACE(UPPER(vehicle_reg), " ", "") = ?', [$formattedReg])
                ->first();
            $this->existingParkedPass = $pass;
        }
    }

    public function confirm()
    {
        \Log::info('Check-in started for congregation: ' . ($this->scannedCongregation->name ?? 'None'));

        if (!$this->scannedCongregation) {
            \Log::warning('Check-in aborted: No congregation scanned.');
            $this->cancel();
            return;
        }

        // Resolve car park: individual (registration) overrides congregation default
        $carPark = $this->resolveEffectiveCarPark();
        if (!$carPark) {
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

        // Format registration for consistency
        $formattedReg = strtoupper(str_replace(' ', '', trim($this->vehicleReg)));

        // Prevent clocking in the same vehicle more than once (any car park)
        if (strlen($formattedReg) >= 2) {
            $alreadyParked = ParkingPass::where('status', 'parked')
                ->get()
                ->contains(fn (ParkingPass $p) => strtoupper(str_replace(' ', '', (string) ($p->vehicle_reg ?? ''))) === $formattedReg);

            if ($alreadyParked) {
                $this->setResult('error', 'ALREADY PARKED', 'This vehicle is already clocked in and cannot be registered again.');
                return;
            }
        }

        // Check Capacity (count by pass car_park_id or legacy: congregation car_park_id)
        $currentOccupancy = ParkingPass::where('status', 'parked')
            ->where(function ($query) use ($carPark) {
                $query->where('car_park_id', $carPark->id)
                    ->orWhere(function ($q) use ($carPark) {
                        $q->whereNull('car_park_id')
                            ->whereHas('congregation', fn ($c) => $c->where('car_park_id', $carPark->id));
                    });
            })
            ->count();

        if ($currentOccupancy >= $carPark->capacity) {
            \Log::warning('Check-in failed: Car park ' . $carPark->name . ' is full.');
            $this->setResult('error', 'CAR PARK FULL', 'The ' . $carPark->name . ' is at capacity (' . $carPark->capacity . ').');
            return;
        }

        if ($currentOccupancy >= ($carPark->capacity * 0.9)) {
            Flux::toast('Warning: ' . $carPark->name . ' is almost full!', variant: 'warning');
        }

        try {
            \Log::info('Attempting to create ParkingPass...');
            $pass = ParkingPass::create([
                'congregation_id' => $this->scannedCongregation->id,
                'car_park_id' => $carPark->id,
                'status' => 'parked',
                'vehicle_reg' => $formattedReg,
                'contact_number' => $this->contactNumber,
                'name' => $this->name,
                'email' => $this->email,
                'days' => $this->days,
                'elderly_infirm_parking' => $this->elderlyInfirmParking,
                'notes' => trim($this->notes) ?: null,
                'scanned_at' => now(),
                'scanned_by_user_id' => auth()->check() ? auth()->id() : null,
            ]);

            \Log::info('ParkingPass created successfully.');

            ParkingRegistration::updateOrCreate(
                ['vehicle_registration' => $formattedReg],
                [
                    'congregation' => $this->scannedCongregation->name,
                    'name' => $this->name ?? '',
                    'contact_number' => $this->contactNumber,
                    'email' => $this->email,
                    'days' => $this->days,
                ]
            );
            \Log::info('ParkingRegistration synced successfully.');
            $this->setResult('success', 'ACCESS GRANTED', $this->scannedCongregation->name . ' -> ' . $carPark->name);

            $pass->load('congregation', 'carPark');
            $this->lastScanPass = $pass;

            $this->reset('uuid', 'step', 'scannedCongregation', 'vehicleReg', 'contactNumber', 'name', 'email', 'days', 'elderlyInfirmParking', 'notes', 'foundRegistration', 'existingParkedPass');
        } catch (\Exception $e) {
            \Log::error('Check-in database error: ' . $e->getMessage());
            Flux::toast('Error: ' . $e->getMessage(), variant: 'danger');
        }
    }

    public function clockOut(int $passId): void
    {
        if (!auth()->check()) {
            return;
        }

        $pass = ParkingPass::where('id', $passId)->where('status', 'parked')->first();

        if (!$pass) {
            Flux::toast('Pass not found or already clocked out.', variant: 'warning');
            $this->existingParkedPass = null;
            return;
        }

        $pass->update([
            'status' => 'left',
            'left_at' => now(),
        ]);

        $reg = $pass->vehicle_reg;
        $this->existingParkedPass = null;
        Flux::toast('Vehicle ' . ($reg ?? '') . ' clocked out.');
    }

    public function checkInAnotherCar(): void
    {
        $this->reset('vehicleReg', 'contactNumber', 'name', 'email', 'days', 'elderlyInfirmParking', 'notes', 'foundRegistration', 'existingParkedPass');
    }

    public function cancel()
    {
        $this->reset('uuid', 'step', 'scannedCongregation', 'vehicleReg', 'contactNumber', 'name', 'email', 'days', 'elderlyInfirmParking', 'notes', 'foundRegistration', 'existingParkedPass');
    }

    /**
     * Resolve which car park to use: individual (registration) overrides congregation.
     * e.g. Person in congregation West (assigned West) but individually assigned East → use East.
     */
    protected function resolveEffectiveCarPark(): ?CarPark
    {
        if ($this->foundRegistration) {
            $reg = ParkingRegistration::find($this->foundRegistration->id);
            if ($reg?->car_park_id) {
                $park = CarPark::find($reg->car_park_id);
                if ($park) {
                    return $park;
                }
            }
        }
        return $this->scannedCongregation->carPark;
    }

    protected function setResult($type, $title, $message)
    {
        $this->lastScanResult = $type;
        $this->lastScanMessage = $title;
        // We can add more details if needed
        if ($type === 'success')
            Flux::toast('Pass Scanned Successfully');
        if ($type === 'error')
            Flux::toast('Invalid Pass', variant: 'danger');
        if ($type === 'warning')
            Flux::toast('Already Scanned', variant: 'warning');
    }
}
