<?php

declare(strict_types=1);

namespace App\Actions\HotelGuestParking;

use App\Models\CarPark;
use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use App\Models\User;
use App\Support\DeferredTicketMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveHotelGuestParkingRequest
{
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

            $registration = ParkingRegistration::query()->create([
                'name' => $locked->name,
                'congregation' => HotelGuestParkingRequest::CONGREGATION_NAME,
                'car_park_id' => $carParkId,
                'vehicle_type' => 'car',
                'vehicle_registration' => $locked->vehicle_registration,
                'contact_number' => $locked->contact_number,
                'email' => $locked->email,
                'days' => is_array($locked->days) ? $locked->days : [],
                'elderly_infirm_parking' => false,
                'sharing_with_other_congregations' => false,
                'sharing_congregations_notes' => null,
                'coach_captain_to_be_assigned' => false,
                'is_circuit_overseer' => false,
            ]);

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

    private function resolveAdminNotes(?string $adminNotes, HotelGuestParkingRequest $request): ?string
    {
        if ($adminNotes === null) {
            return $request->admin_notes;
        }

        $trimmed = trim($adminNotes);

        return $trimmed !== '' ? $trimmed : null;
    }
}
