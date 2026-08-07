<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketChangeRequestDeclinedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<string>  $ccAddresses
     */
    public function __construct(
        public string $recipientEmail,
        public string $requesterName,
        public string $congregation,
        public array $ccAddresses = [],
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->congregation !== '' ? $this->congregation.' — ' : '';

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: $label.__('mail.ticket_change_declined.subject'),
            cc: $this->ccAddresses,
        );
    }

    public function content(): Content
    {
        $name = e($this->requesterName !== '' ? $this->requesterName : 'there');
        $body = e(__('mail.ticket_change_declined.body'));
        $regards = e(__('mail.ticket_change_declined.regards'));
        $team = e(__('mail.ticket_change_declined.team'));

        $html = <<<HTML
<p>Hello {$name},</p>
<p>{$body}</p>
<p>{$regards},<br>{$team}</p>
HTML;

        return new Content(htmlString: $html);
    }
}
