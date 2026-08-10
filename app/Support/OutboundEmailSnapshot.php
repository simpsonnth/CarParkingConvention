<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Mail\Mailable;
use Throwable;

final class OutboundEmailSnapshot
{
    /**
     * @param  list<array{filename: string, registration_id: int, label?: string}>  $attachments
     * @return array{subject: string, body_html: string, attachments: list<array{filename: string, registration_id: int, label?: string}>}
     */
    public static function fromMailable(Mailable $mailable, array $attachments = []): array
    {
        $subject = '';
        try {
            $subject = trim((string) ($mailable->envelope()->subject ?? ''));
        } catch (Throwable) {
            $subject = '';
        }

        $bodyHtml = '';
        try {
            $bodyHtml = (string) $mailable->render();
        } catch (Throwable) {
            $bodyHtml = '';
        }

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'attachments' => array_values($attachments),
        ];
    }
}
