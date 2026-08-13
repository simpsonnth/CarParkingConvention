<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationBroadcastMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $recipientEmail,
        public string $recipientName,
        public string $emailSubject,
        public string $emailBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        $name = trim($this->recipientName) !== '' ? trim($this->recipientName) : 'there';
        $body = str_replace(
            ['{{name}}', '{{ name }}'],
            $name,
            $this->emailBody,
        );

        $safeBody = nl2br(e($body), false);
        $regards = e(__('mail.registration_broadcast.regards'));
        $team = e(__('mail.registration_broadcast.team'));

        $html = <<<HTML
<p>{$safeBody}</p>
<p>{$regards},<br>{$team}</p>
HTML;

        return new Content(htmlString: $html);
    }
}
