<?php

declare(strict_types=1);

namespace App\Actions\HotelGuestParking;

use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use Illuminate\Support\Facades\DB;

class DeleteHotelGuestParkingRequest
{
    /**
     * Permanently remove the hotel-guest request and soft-delete any linked parking registration.
     */
    public function execute(HotelGuestParkingRequest $request): void
    {
        DB::transaction(function () use ($request): void {
            /** @var HotelGuestParkingRequest $locked */
            $locked = HotelGuestParkingRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            $registrationId = $locked->parking_registration_id;

            $locked->delete();

            if ($registrationId === null) {
                return;
            }

            $registration = ParkingRegistration::query()->find($registrationId);
            if ($registration !== null) {
                $registration->delete();
            }
        });
    }
}
