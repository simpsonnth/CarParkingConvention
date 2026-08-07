<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Actions\HotelGuestParking\SubmitHotelGuestParkingRequest;
use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use App\Services\ParkingRegistrationDuplicateSignals;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class RadissonGuestParking extends Component
{
    public string $name = '';

    public string $contactNumber = '';

    public string $vehicleReg = '';

    public string $email = '';

    /** @var list<string> */
    public array $days = [];

    public bool $submitted = false;

    public function toggleAllDays(): void
    {
        if (count($this->days) === count(HotelGuestParkingRequest::ALLOWED_DAYS)) {
            $this->days = [];
        } else {
            $this->days = HotelGuestParkingRequest::ALLOWED_DAYS;
        }
    }

    public function submitAnother(): void
    {
        $this->reset([
            'name',
            'contactNumber',
            'vehicleReg',
            'email',
            'days',
            'submitted',
        ]);
        unset($this->duplicateVehicleRegistrationConflict, $this->duplicateEmailExistingRegistration);
    }

    public function submit(SubmitHotelGuestParkingRequest $submit): void
    {
        $this->resetErrorBag();

        try {
            $submit->execute([
                'name' => $this->name,
                'contact_number' => $this->contactNumber,
                'vehicle_registration' => $this->vehicleReg,
                'email' => $this->email,
                'days' => $this->days,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($this->mapErrorField((string) $field), $message);
                }
            }

            return;
        }

        $this->submitted = true;

        try {
            Flux::toast(__('radisson_guest_parking.complete_title'));
        } catch (\Throwable) {
            session()->flash('status', __('radisson_guest_parking.complete_title'));
        }
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

    public function render()
    {
        return view('livewire.public.radisson-guest-parking');
    }

    private function mapErrorField(string $field): string
    {
        return match ($field) {
            'contact_number' => 'contactNumber',
            'vehicle_registration' => 'vehicleReg',
            default => $field,
        };
    }
}
