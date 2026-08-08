<?php

declare(strict_types=1);

namespace App\Actions\HotelGuestParking;

use App\Models\HotelGuestParkingRequest;
use App\Models\User;
use App\Support\DeferredTicketMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectHotelGuestParkingRequest
{
    public function execute(
        HotelGuestParkingRequest $request,
        User $actor,
        ?string $adminNotes = null,
    ): HotelGuestParkingRequest {
        $completed = DB::transaction(function () use ($request, $actor, $adminNotes): HotelGuestParkingRequest {
            /** @var HotelGuestParkingRequest $locked */
            $locked = HotelGuestParkingRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'decline' => __('management.hotel_guest_parking.already_actioned'),
                ]);
            }

            if (! filled($locked->email) || ! filter_var((string) $locked->email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'decline' => __('management.hotel_guest_parking.decline_email_required'),
                ]);
            }

            $defaultNote = __('management.hotel_guest_parking.declined_default_note');
            $note = $adminNotes !== null ? trim($adminNotes) : '';
            if ($note === '') {
                $note = filled($locked->admin_notes) ? (string) $locked->admin_notes : $defaultNote;
            }

            $locked->update([
                'status' => HotelGuestParkingRequest::STATUS_REJECTED,
                'actioned_at' => now(),
                'actioned_by' => $actor->id,
                'admin_notes' => $note,
            ]);

            return $locked->fresh() ?? $locked;
        });

        DeferredTicketMail::sendHotelGuestParkingDecline(
            toEmail: (string) $completed->email,
            requesterName: (string) $completed->name,
        );

        return $completed;
    }
}
