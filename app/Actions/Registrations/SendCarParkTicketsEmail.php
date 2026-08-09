<?php

declare(strict_types=1);

namespace App\Actions\Registrations;

use App\Mail\CarParkTicketsMail;
use App\Models\ParkingRegistration;
use App\Services\MasterPassPdfGenerator;
use App\Support\TicketEmailCcList;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendCarParkTicketsEmail
{
    public function __construct(
        protected MasterPassPdfGenerator $pdfGenerator,
    ) {}

    /**
     * @param  list<int>  $registrationIds
     * @return array{sent: int, to: string, cc: list<string>}
     */
    public function execute(array $registrationIds, string $toEmail, ?string $note = null): array
    {
        // Large batches of Chrome PDFs need more than the default 30s.
        @set_time_limit(300);
        ini_set('max_execution_time', '300');

        $to = strtolower(trim($toEmail));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'ticketEmailTo' => 'Please enter a valid email address.',
            ]);
        }

        $ids = array_values(array_unique(array_map('intval', $registrationIds)));
        if ($ids === []) {
            throw ValidationException::withMessages([
                'ticketEmailTo' => 'Select at least one registration.',
            ]);
        }

        $attachments = $this->pdfGenerator->generateForIds($ids);
        $pdfPayload = array_map(
            fn (array $row): array => [
                'filename' => $row['filename'],
                'content' => $row['content'],
            ],
            $attachments,
        );

        $registrations = collect($attachments)->pluck('registration');
        $congregationLabel = $this->congregationLabel($registrations);

        $cc = array_values(array_filter(
            TicketEmailCcList::all(),
            fn (string $email): bool => $email !== $to,
        ));

        $trimmedNote = $note !== null ? trim($note) : '';

        Mail::to($to)->send(new CarParkTicketsMail(
            recipientEmail: $to,
            ticketCount: count($pdfPayload),
            congregationLabel: $congregationLabel,
            pdfAttachments: $pdfPayload,
            ccAddresses: $cc,
            note: $trimmedNote !== '' ? $trimmedNote : null,
        ));

        $sentIds = $registrations
            ->map(fn (ParkingRegistration $registration): int => (int) $registration->id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($sentIds !== []) {
            ParkingRegistration::query()
                ->whereIn('id', $sentIds)
                ->update(['ticket_sent_at' => now()]);
        }

        return [
            'sent' => count($pdfPayload),
            'to' => $to,
            'cc' => $cc,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ParkingRegistration>  $registrations
     */
    protected function congregationLabel($registrations): string
    {
        $names = $registrations
            ->map(function (ParkingRegistration $registration): string {
                if ($registration->is_circuit_overseer) {
                    return 'Circuit Overseer';
                }

                return trim((string) $registration->congregation);
            })
            ->filter()
            ->unique()
            ->values();

        if ($names->count() === 1) {
            return (string) $names->first();
        }

        if ($names->count() > 1) {
            return $names->count().' congregations';
        }

        return '';
    }
}
