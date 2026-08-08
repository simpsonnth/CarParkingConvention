<?php

declare(strict_types=1);

namespace App\Actions\HotelGuestParking;

use App\Models\HotelGuestParkingRequest;
use App\Rules\PersonalEmail;
use App\Support\VehicleRegistrationNormalizer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmitHotelGuestParkingRequest
{
    /**
     * @param  array{
     *     name: string,
     *     contact_number: string,
     *     vehicle_registration: string,
     *     email: string,
     *     days: list<string>,
     * }  $input
     */
    public function execute(array $input): HotelGuestParkingRequest
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'vehicle_registration' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', new PersonalEmail],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['string', Rule::in(HotelGuestParkingRequest::ALLOWED_DAYS)],
        ], [
            'days.required' => __('radisson_guest_parking.validation.days_required'),
            'days.min' => __('radisson_guest_parking.validation.days_required'),
        ])->validate();

        $vehicleReg = VehicleRegistrationNormalizer::normalize(
            (string) $validated['vehicle_registration'],
            'car',
        );

        if ($vehicleReg === null || $vehicleReg === '') {
            throw ValidationException::withMessages([
                'vehicle_registration' => __('radisson_guest_parking.validation.vehicle_registration_required'),
            ]);
        }

        $days = array_values(array_intersect(
            HotelGuestParkingRequest::ALLOWED_DAYS,
            $validated['days'],
        ));

        if ($days === []) {
            throw ValidationException::withMessages([
                'days' => __('radisson_guest_parking.validation.days_required'),
            ]);
        }

        $duplicatePending = HotelGuestParkingRequest::query()
            ->where('status', HotelGuestParkingRequest::STATUS_PENDING)
            ->where('vehicle_registration', $vehicleReg)
            ->exists();

        if ($duplicatePending) {
            throw ValidationException::withMessages([
                'vehicle_registration' => __('radisson_guest_parking.validation.pending_duplicate'),
            ]);
        }

        return HotelGuestParkingRequest::query()->create([
            'name' => trim((string) $validated['name']),
            'contact_number' => trim((string) $validated['contact_number']),
            'vehicle_registration' => $vehicleReg,
            'email' => strtolower(trim((string) $validated['email'])),
            'days' => $days,
            'status' => HotelGuestParkingRequest::STATUS_PENDING,
        ]);
    }
}
