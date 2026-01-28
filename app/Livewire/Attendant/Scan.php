<?php

namespace App\Livewire\Attendant;

use App\Models\ParkingPass;
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
        return view('livewire.attendant.scan');
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
        $this->reset('vehicleReg'); // Clear previous input
    }

    public $foundRegistration = null;

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
            $reg = \App\Models\ParkingRegistration::where('vehicle_registration', $this->vehicleReg)->first();

            if ($reg) {
                $this->foundRegistration = $reg;
                $this->contactNumber = $reg->contact_number; // Autofill
                $this->name = $reg->name;
                $this->email = $reg->email;
                $this->days = $reg->days ?? [];
                // Flux::toast('Registration Found: ' . $reg->name); 
            } else {
                $this->foundRegistration = null;
                // Keep contact number if user typed it, or reset? 
                // Better to not reset if they typed it manually, but if it was autofilled from previous check, maybe?
                // For now, let's leave it.
            }
        } else {
            $this->foundRegistration = null;
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

        $this->validate([
            'vehicleReg' => 'required|string|min:2',
            'contactNumber' => 'required|string|min:6',
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'days' => 'nullable|array',
        ]);

        // Format registration for consistency
        $formattedReg = strtoupper(str_replace(' ', '', trim($this->vehicleReg)));

        // Check for duplicate vehicle
        if ($formattedReg) {
            \Log::info('Checking duplicate for: ' . $formattedReg);
            $existingPass = ParkingPass::whereRaw('replace(upper(vehicle_reg), " ", "") = ?', [$formattedReg])
                ->where('status', 'parked')
                ->exists();

            if ($existingPass) {
                \Log::warning('Duplicate entry found for: ' . $formattedReg);
                $this->setResult('error', 'DUPLICATE ENTRY', 'Vehicle ' . $this->vehicleReg . ' is already parked.');
                return;
            }
        }

        // Check Capacity
        $carPark = $this->scannedCongregation->carPark;
        $currentOccupancy = ParkingPass::whereHas('congregation', function ($query) use ($carPark) {
            $query->where('car_park_id', $carPark->id);
        })->where('status', 'parked')->count();

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
            // Create a new Parking Pass entry
            $pass = ParkingPass::create([
                'congregation_id' => $this->scannedCongregation->id,
                'status' => 'parked',
                'vehicle_reg' => $formattedReg,
                'contact_number' => $this->contactNumber,
                'name' => $this->name,
                'email' => $this->email,
                'days' => $this->days,
                'scanned_at' => now(),
                'scanned_by_user_id' => auth()->check() ? auth()->id() : null,
            ]);

            \Log::info('ParkingPass created successfully.');

            // Sync with Registrations Table
            \App\Models\ParkingRegistration::updateOrCreate(
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
            $this->setResult('success', 'ACCESS GRANTED', $this->scannedCongregation->name . ' -> ' . $this->scannedCongregation->carPark->name);

            $pass->load('congregation.carPark');
            $this->lastScanPass = $pass;

            $this->reset('uuid', 'step', 'scannedCongregation', 'vehicleReg', 'contactNumber', 'name', 'email', 'days', 'foundRegistration');
        } catch (\Exception $e) {
            \Log::error('Check-in database error: ' . $e->getMessage());
            Flux::toast('Error: ' . $e->getMessage(), variant: 'danger');
        }
    }

    public function cancel()
    {
        $this->reset('uuid', 'step', 'scannedCongregation', 'vehicleReg', 'contactNumber', 'name', 'email', 'days', 'foundRegistration');
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
