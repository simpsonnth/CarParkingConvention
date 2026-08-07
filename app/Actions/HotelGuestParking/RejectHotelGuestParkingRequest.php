<?php

declare(strict_types=1);

namespace App\Actions\HotelGuestParking;

use App\Models\HotelGuestParkingRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectHotelGuestParkingRequest
{
    public function execute(
        HotelGuestParkingRequest $request,
        User $actor,
        ?string $adminNotes = null,
    ): HotelGuestParkingRequest {
        return DB::transaction(function () use ($request, $actor, $adminNotes): HotelGuestParkingRequest {
            /** @var HotelGuestParkingRequest $locked */
            $locked = HotelGuestParkingRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'reject' => __('management.hotel_guest_parking.already_actioned'),
                ]);
            }

            $note = $adminNotes !== null ? trim($adminNotes) : '';
            if ($note === '' && filled($locked->admin_notes)) {
                $note = (string) $locked->admin_notes;
            }

            $locked->update([
                'status' => HotelGuestParkingRequest::STATUS_REJECTED,
                'actioned_at' => now(),
                'actioned_by' => $actor->id,
                'admin_notes' => $note !== '' ? $note : null,
            ]);

            return $locked->fresh() ?? $locked;
        });
    }
}
