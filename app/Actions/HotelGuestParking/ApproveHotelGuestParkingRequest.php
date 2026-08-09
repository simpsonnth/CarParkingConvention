<?php

declare(strict_types=1);

namespace App\Actions\HotelGuestParking;

use App\Models\CarPark;
use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use App\Models\User;
use App\Services\ParkingRegistrationDuplicateSignals;
use App\Support\ConventionDay;
use App\Support\DeferredTicketMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveHotelGuestParkingRequest
{
    public function __construct(
        private readonly ParkingRegistrationDuplicateSignals $duplicateSignals,
    ) {}

    public function execute(
        HotelGuestParkingRequest $request,
        User $actor,
        int $carParkId,
        ?string $adminNotes = null,
    ): HotelGuestParkingRequest {
        if (! CarPark::query()->whereKey($carParkId)->exists()) {
            throw ValidationException::withMessages([
                'approveCarParkId' => __('management.hotel_guest_parking.car_park_required'),
            ]);
        }

        $completed = DB::transaction(function () use ($request, $actor, $carParkId, $adminNotes): HotelGuestParkingRequest {
            /** @var HotelGuestParkingRequest $locked */
            $locked = HotelGuestParkingRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'approve' => __('management.hotel_guest_parking.already_actioned'),
                ]);
            }

            HotelGuestParkingRequest::ensureCongregation($carParkId);

            $registration = $this->resolveRegistration($locked, $carParkId);

            $locked->update([
                'status' => HotelGuestParkingRequest::STATUS_APPROVED,
                'car_park_id' => $carParkId,
                'parking_registration_id' => $registration->id,
                'actioned_at' => now(),
                'actioned_by' => $actor->id,
                'admin_notes' => $this->resolveAdminNotes($adminNotes, $locked),
            ]);

            return $locked->fresh() ?? $locked;
        });

        if (filled($completed->email) && $completed->parking_registration_id !== null) {
            DeferredTicketMail::sendCarParkTickets(
                [$completed->parking_registration_id],
                (string) $completed->email,
            );
        }

        return $completed;
    }

    /**
     * Update an existing ticket for the same VRN, or create a new Radisson Hotel Guest registration.
     */
    private function resolveRegistration(HotelGuestParkingRequest $locked, int $carParkId): ParkingRegistration
    {
        $payload = [
            'name' => $locked->name,
            'congregation' => HotelGuestParkingRequest::CONGREGATION_NAME,
            'car_park_id' => $carParkId,
            'vehicle_type' => 'car',
            'vehicle_registration' => $locked->vehicle_registration,
            'contact_number' => $locked->contact_number,
            'email' => $locked->email,
            'days' => $this->registrationDaysForHotelStay(
                is_array($locked->days) ? $locked->days : [],
            ),
            'elderly_infirm_parking' => false,
            'sharing_with_other_congregations' => false,
            'sharing_congregations_notes' => null,
            'coach_captain_to_be_assigned' => false,
            'is_circuit_overseer' => false,
        ];

        $existing = $this->duplicateSignals->findActiveByNormalizedVehicleReg(
            $this->duplicateSignals->normalizeVehicleRegistration($locked->vehicle_registration),
        );

        if ($existing !== null) {
            $existing->update($payload);

            return $existing->fresh() ?? $existing;
        }

        return ParkingRegistration::query()->create($payload);
    }

    private function resolveAdminNotes(?string $adminNotes, HotelGuestParkingRequest $request): ?string
    {
        if ($adminNotes === null) {
            return $request->admin_notes;
        }

        $trimmed = trim($adminNotes);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Hotel nights stay on the ticket, and Fri/Sat/Sun are always included because
     * Radisson guests are assumed to attend all three convention days.
     *
     * @param  list<string>  $hotelNights
     * @return list<string>
     */
    private function registrationDaysForHotelStay(array $hotelNights): array
    {
        $allowed = HotelGuestParkingRequest::ALLOWED_DAYS;
        $merged = array_unique([
            ...array_intersect($allowed, $hotelNights),
            ...ConventionDay::singleDayKeys(),
        ]);

        return array_values(array_intersect($allowed, $merged));
    }
}
