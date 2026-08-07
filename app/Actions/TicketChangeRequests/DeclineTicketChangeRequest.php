<?php

declare(strict_types=1);

namespace App\Actions\TicketChangeRequests;

use App\Models\TicketChangeRequest;
use App\Models\User;
use App\Support\DeferredTicketMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeclineTicketChangeRequest
{
    public function execute(
        TicketChangeRequest $request,
        User $actor,
        ?string $adminNotes = null,
    ): TicketChangeRequest {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'decline' => __('management.ticket_change_requests.already_completed'),
            ]);
        }

        if (! filled($request->notification_email)) {
            throw ValidationException::withMessages([
                'decline' => __('management.ticket_change_requests.decline_email_required'),
            ]);
        }

        $completed = DB::transaction(function () use ($request, $actor, $adminNotes): TicketChangeRequest {
            /** @var TicketChangeRequest $locked */
            $locked = TicketChangeRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'decline' => __('management.ticket_change_requests.already_completed'),
                ]);
            }

            $defaultNote = __('management.ticket_change_requests.declined_default_note');
            $note = $adminNotes !== null ? trim($adminNotes) : '';
            if ($note === '') {
                $note = filled($locked->admin_notes) ? (string) $locked->admin_notes : $defaultNote;
            }

            $locked->update([
                'status' => TicketChangeRequest::STATUS_COMPLETED,
                'actioned_at' => now(),
                'actioned_by' => $actor->id,
                'admin_notes' => $note,
            ]);

            return $locked->fresh() ?? $locked;
        });

        DeferredTicketMail::sendDecline(
            toEmail: (string) $completed->notification_email,
            requesterName: (string) $completed->name,
            congregation: (string) $completed->congregation,
        );

        return $completed;
    }
}
