<?php

declare(strict_types=1);

namespace App\Actions\HotelGuestParking;

use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use App\Services\ParkingRegistrationDuplicateSignals;
use App\Support\VehicleRegistrationNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateHotelGuestParkingRequest
{
    public function __construct(
        private readonly ParkingRegistrationDuplicateSignals $duplicateSignals,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     contact_number: string,
     *     vehicle_registration: string,
     *     email: string,
     *     days: list<string>,
     * }  $input
     */
    public function execute(HotelGuestParkingRequest $request, array $input): HotelGuestParkingRequest
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'vehicle_registration' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['string', Rule::in(HotelGuestParkingRequest::ALLOWED_DAYS)],
        ], [
            'days.required' => __('management.hotel_guest_parking.edit_days_required'),
            'days.min' => __('management.hotel_guest_parking.edit_days_required'),
        ])->validate();

        $vehicleReg = VehicleRegistrationNormalizer::normalize(
            (string) $validated['vehicle_registration'],
            'car',
        );

        if ($vehicleReg === null || $vehicleReg === '') {
            throw ValidationException::withMessages([
                'editVehicleRegistration' => __('management.hotel_guest_parking.edit_vehicle_required'),
            ]);
        }

        $days = array_values(array_intersect(
            HotelGuestParkingRequest::ALLOWED_DAYS,
            $validated['days'],
        ));

        if ($days === []) {
            throw ValidationException::withMessages([
                'editDays' => __('management.hotel_guest_parking.edit_days_required'),
            ]);
        }

        $payload = [
            'name' => trim((string) $validated['name']),
            'contact_number' => trim((string) $validated['contact_number']),
            'vehicle_registration' => $vehicleReg,
            'email' => strtolower(trim((string) $validated['email'])),
            'days' => $days,
        ];

        return DB::transaction(function () use ($request, $payload, $vehicleReg): HotelGuestParkingRequest {
            /** @var HotelGuestParkingRequest $locked */
            $locked = HotelGuestParkingRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            $duplicatePending = HotelGuestParkingRequest::query()
                ->where('status', HotelGuestParkingRequest::STATUS_PENDING)
                ->where('vehicle_registration', $vehicleReg)
                ->whereKeyNot($locked->id)
                ->exists();

            if ($duplicatePending) {
                throw ValidationException::withMessages([
                    'editVehicleRegistration' => __('management.hotel_guest_parking.edit_pending_duplicate'),
                ]);
            }

            $linkedRegistration = null;
            if ($locked->parking_registration_id !== null) {
                $linkedRegistration = ParkingRegistration::query()
                    ->whereKey($locked->parking_registration_id)
                    ->lockForUpdate()
                    ->first();
            }

            if ($linkedRegistration !== null) {
                $conflicting = $this->duplicateSignals->findActiveByNormalizedVehicleReg(
                    $this->duplicateSignals->normalizeVehicleRegistration($vehicleReg),
                );

                if ($conflicting !== null && (int) $conflicting->id !== (int) $linkedRegistration->id) {
                    throw ValidationException::withMessages([
                        'editVehicleRegistration' => __('management.hotel_guest_parking.edit_registration_conflict', [
                            'ticket' => $conflicting->ticketNumber(),
                            'name' => $conflicting->name,
                        ]),
                    ]);
                }

                $linkedRegistration->update([
                    'name' => $payload['name'],
                    'contact_number' => $payload['contact_number'],
                    'vehicle_registration' => $payload['vehicle_registration'],
                    'email' => $payload['email'],
                    'days' => HotelGuestParkingRequest::registrationDaysForHotelStay($payload['days']),
                ]);
            }

            $locked->update($payload);

            return $locked->fresh() ?? $locked;
        });
    }
}
