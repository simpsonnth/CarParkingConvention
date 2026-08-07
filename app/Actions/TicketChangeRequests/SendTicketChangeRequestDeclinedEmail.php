<?php

declare(strict_types=1);

namespace App\Actions\TicketChangeRequests;

use App\Mail\TicketChangeRequestDeclinedMail;
use App\Support\TicketEmailCcList;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendTicketChangeRequestDeclinedEmail
{
    public function execute(
        string $toEmail,
        string $requesterName,
        string $congregation,
    ): array {
        $to = strtolower(trim($toEmail));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'decline' => __('management.ticket_change_requests.decline_email_required'),
            ]);
        }

        $cc = array_values(array_filter(
            TicketEmailCcList::all(),
            fn (string $email): bool => $email !== $to,
        ));

        Mail::to($to)->send(new TicketChangeRequestDeclinedMail(
            recipientEmail: $to,
            requesterName: $requesterName,
            congregation: $congregation,
            ccAddresses: $cc,
        ));

        return [
            'to' => $to,
            'cc' => $cc,
        ];
    }
}
