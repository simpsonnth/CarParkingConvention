<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketCancellationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<string>  $ccAddresses
     */
    public function __construct(
        public string $recipientEmail,
        public string $ticketNumber,
        public string $congregation,
        public string $driverName,
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
            subject: $label.'Car park ticket cancelled ('.$this->ticketNumber.')',
            cc: $this->ccAddresses,
        );
    }

    public function content(): Content
    {
        $name = e($this->driverName !== '' ? $this->driverName : 'there');
        $ticket = e($this->ticketNumber);
        $congregation = e($this->congregation);

        $html = <<<HTML
<p>Hello {$name},</p>
<p>Your car park ticket <strong>{$ticket}</strong> for <strong>{$congregation}</strong> has been cancelled as requested.</p>
<p>If you need a new ticket, please submit an addition request or contact the convention parking team.</p>
<p>Kind regards,<br>Convention Parking Team</p>
HTML;

        return new Content(htmlString: $html);
    }
}
