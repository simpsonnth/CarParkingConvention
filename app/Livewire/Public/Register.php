<?php

namespace App\Livewire\Public;

use App\Models\Congregation;
use App\Models\ParkingRegistration;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Register extends Component
{
    public $vehicleType = 'car'; // 'car' or 'coach'

    /** Coach only: Are you sharing with other congregations? '0' or '1' */
    public $sharingWithOtherCongregations = '0';

    /** Coach only: When sharing = Yes, list of congregations */
    public $sharingCongregationsNotes = '';

    public $congregationCode = '';

    public $name = '';

    public $contactNumber = '';

    public $vehicleReg = '';

    public $days = []; // ['Friday', 'Saturday', 'Sunday']

    public $email = '';

    public $elderlyInfirmParking = '0'; // Do you need parking for Elderly and Infirm? yes/no (string '0'/'1' for radio binding)

    public $registered = false;

    protected static array $allDays = ['Friday', 'Saturday', 'Sunday'];

    #[Layout('components.layouts.public')]
    #[Computed]
    public function resolvedCongregation(): ?Congregation
    {
        $code = trim($this->congregationCode);
        if ($code === '') {
            return null;
        }
        return Congregation::where('uuid', $code)->first();
    }

    public function render()
    {
        return view('livewire.public.register');
    }

    public function toggleAllDays(): void
    {
        if (count($this->days) === count(self::$allDays)) {
            $this->days = [];
        } else {
            $this->days = self::$allDays;
        }
    }

    public function register(): void
    {
        $congregation = $this->resolvedCongregation;

        $rules = [
            'vehicleType' => 'required|in:car,coach',
            'congregationCode' => 'required|string',
            'name' => 'required|string|max:255',
            'contactNumber' => 'required|string|max:255',
            'days' => 'required|array|min:1',
            'email' => 'required|email|max:255',
        ];

        if ($this->vehicleType === 'car') {
            $rules['vehicleReg'] = 'required|string|max:20|uppercase';
            $rules['elderlyInfirmParking'] = 'required|in:0,1';
        } else {
            $rules['vehicleReg'] = 'nullable|string|max:20|uppercase';
            $rules['elderlyInfirmParking'] = 'nullable|in:0,1';
            $rules['sharingWithOtherCongregations'] = 'required|in:0,1';
            if ($this->sharingWithOtherCongregations === '1') {
                $rules['sharingCongregationsNotes'] = 'required|string|max:1000';
            } else {
                $rules['sharingCongregationsNotes'] = 'nullable|string|max:1000';
            }
        }

        $this->validate($rules);

        if (!$congregation) {
            $this->addError('congregationCode', __('register.invalid_congregation_code'));
            return;
        }

        $formattedReg = $this->vehicleType === 'car' && trim($this->vehicleReg) !== ''
            ? strtoupper(str_replace(' ', '', trim($this->vehicleReg)))
            : null;

        ParkingRegistration::create([
            'name' => $this->name,
            'congregation' => $congregation->name,
            'contact_number' => $this->contactNumber,
            'vehicle_registration' => $formattedReg,
            'days' => $this->days,
            'email' => $this->email,
            'vehicle_type' => $this->vehicleType,
            'sharing_with_other_congregations' => $this->vehicleType === 'coach'
                ? filter_var($this->sharingWithOtherCongregations, FILTER_VALIDATE_BOOLEAN)
                : false,
            'sharing_congregations_notes' => $this->vehicleType === 'coach' && $this->sharingWithOtherCongregations === '1'
                ? trim($this->sharingCongregationsNotes)
                : null,
            'elderly_infirm_parking' => $this->vehicleType === 'car'
                ? filter_var($this->elderlyInfirmParking, FILTER_VALIDATE_BOOLEAN)
                : false,
        ]);

        $this->registered = true;

        try {
            Flux::toast('Registration Successful!');
        } catch (\Throwable $e) {
            session()->flash('status', 'Registration Successful!');
        }
    }
}
