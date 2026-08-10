<?php

declare(strict_types=1);

namespace App\Actions\TicketChangeRequests;

use App\Mail\TicketCancellationMail;
use App\Support\OutboundEmailSnapshot;
use App\Support\TicketEmailCcList;
use App\Support\TransactionalMail;
use Illuminate\Validation\ValidationException;

class SendTicketCancellationEmail
{
    public function execute(
        string $toEmail,
        string $ticketNumber,
        string $congregation,
        string $driverName,
    ): array {
        $to = strtolower(trim($toEmail));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'notification_email' => __('ticket_change_request.validation.notification_email'),
            ]);
        }

        $cc = array_values(array_filter(
            TicketEmailCcList::all(),
            fn (string $email): bool => $email !== $to,
        ));

        $mailable = new TicketCancellationMail(
            recipientEmail: $to,
            ticketNumber: $ticketNumber,
            congregation: $congregation,
            driverName: $driverName,
            ccAddresses: $cc,
        );
        $snapshot = OutboundEmailSnapshot::fromMailable($mailable);

        $result = TransactionalMail::send($mailable, $to);

        return [
            'to' => $to,
            'cc' => $cc,
            'mailer' => $result['mailer'],
            'subject' => $snapshot['subject'],
            'body_html' => $snapshot['body_html'],
            'attachments' => $snapshot['attachments'],
        ];
    }
}
