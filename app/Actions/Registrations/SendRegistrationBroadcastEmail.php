<?php

declare(strict_types=1);

namespace App\Actions\Registrations;

use App\Mail\RegistrationBroadcastMail;
use App\Support\OutboundEmailSnapshot;
use App\Support\TransactionalMail;
use Illuminate\Validation\ValidationException;

class SendRegistrationBroadcastEmail
{
    /**
     * @return array{to: string, mailer: string, subject: string, body_html: string, attachments: list<mixed>}
     */
    public function execute(
        string $toEmail,
        string $recipientName,
        string $subject,
        string $body,
    ): array {
        $to = strtolower(trim($toEmail));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => __('registrations.broadcast_email_invalid'),
            ]);
        }

        $subject = trim($subject);
        $body = trim($body);
        if ($subject === '' || $body === '') {
            throw ValidationException::withMessages([
                'subject' => __('registrations.broadcast_subject_body_required'),
            ]);
        }

        $mailable = new RegistrationBroadcastMail(
            recipientEmail: $to,
            recipientName: $recipientName,
            emailSubject: $subject,
            emailBody: $body,
        );
        $snapshot = OutboundEmailSnapshot::fromMailable($mailable);
        $result = TransactionalMail::send($mailable, $to);

        return [
            'to' => $to,
            'mailer' => $result['mailer'],
            'subject' => $snapshot['subject'],
            'body_html' => $snapshot['body_html'],
            'attachments' => $snapshot['attachments'],
        ];
    }
}
