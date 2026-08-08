<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HotelGuestParkingRequestDeclinedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<string>  $ccAddresses
     */
    public function __construct(
        public string $recipientEmail,
        public string $requesterName,
        public array $ccAddresses = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: __('mail.hotel_guest_parking_declined.subject'),
            cc: $this->ccAddresses,
        );
    }

    public function content(): Content
    {
        $name = e($this->requesterName !== '' ? $this->requesterName : 'there');
        $body = e(__('mail.hotel_guest_parking_declined.body'));
        $regards = e(__('mail.hotel_guest_parking_declined.regards'));
        $team = e(__('mail.hotel_guest_parking_declined.team'));

        $html = <<<HTML
<p>Hello {$name},</p>
<p>{$body}</p>
<p>{$regards},<br>{$team}</p>
HTML;

        return new Content(htmlString: $html);
    }
}
