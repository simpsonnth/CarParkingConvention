<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\TicketEmailBody;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CarParkTicketsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<array{filename: string, content: string}>  $pdfAttachments
     * @param  list<string>  $ccAddresses
     */
    public function __construct(
        public string $recipientEmail,
        public int $ticketCount,
        public string $congregationLabel,
        public array $pdfAttachments,
        public array $ccAddresses = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: $this->emailSubject(),
            cc: $this->ccAddresses,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: TicketEmailBody::renderHtml($this->ticketCount, $this->congregationLabel),
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return array_map(
            fn (array $pdf): Attachment => Attachment::fromData(
                fn () => $pdf['content'],
                $pdf['filename'],
            )->withMime('application/pdf'),
            $this->pdfAttachments,
        );
    }

    protected function emailSubject(): string
    {
        $label = $this->congregationLabel !== '' ? $this->congregationLabel.' — ' : '';

        return $label.'Car park tickets ('.$this->ticketCount.')';
    }
}
