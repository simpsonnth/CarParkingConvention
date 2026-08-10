<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\HotelGuestParking\SendHotelGuestParkingDeclinedEmail;
use App\Actions\Registrations\SendCarParkTicketsEmail;
use App\Actions\TicketChangeRequests\SendTicketCancellationEmail;
use App\Actions\TicketChangeRequests\SendTicketChangeRequestDeclinedEmail;
use App\Models\OutboundEmail;
use App\Support\MailSendingQuota;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class OutboundEmailProcessor
{
    public const MAX_ATTEMPTS = 8;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function enqueue(
        string $type,
        string $toEmail,
        array $payload,
        ?CarbonInterface $availableAt = null,
    ): OutboundEmail {
        $to = strtolower(trim($toEmail));

        $existing = OutboundEmail::query()
            ->where('status', OutboundEmail::STATUS_PENDING)
            ->where('type', $type)
            ->where('to_email', $to)
            ->orderByDesc('id')
            ->get()
            ->first(function (OutboundEmail $row) use ($payload): bool {
                return ($row->payload ?? []) == $payload;
            });

        if ($existing !== null) {
            if ($availableAt !== null && ($existing->available_at === null || $existing->available_at->lt($availableAt))) {
                $existing->update(['available_at' => $availableAt]);
            }

            return $existing->fresh() ?? $existing;
        }

        return OutboundEmail::query()->create([
            'type' => $type,
            'status' => OutboundEmail::STATUS_PENDING,
            'to_email' => $to,
            'payload' => $payload,
            'available_at' => $availableAt ?? (MailSendingQuota::isBlocked() ? MailSendingQuota::availableAt() : null),
            'attempts' => 0,
        ]);
    }

    /**
     * @param  list<int>  $registrationIds
     * @return array{status: string, email: OutboundEmail, to: string, available_at: ?CarbonInterface}
     */
    public function sendCarParkTicketsNowOrQueue(
        array $registrationIds,
        string $toEmail,
        ?string $note = null,
    ): array {
        $payload = [
            'registration_ids' => array_values(array_unique(array_map('intval', $registrationIds))),
            'note' => $note,
        ];

        $email = $this->enqueue(OutboundEmail::TYPE_CAR_PARK_TICKETS, $toEmail, $payload);

        return $this->attemptAndDescribe($email);
    }

    /**
     * @return array{status: string, email: OutboundEmail, to: string, available_at: ?CarbonInterface}
     */
    public function attemptAndDescribe(OutboundEmail $email): array
    {
        $result = $this->process($email->fresh() ?? $email);
        $fresh = $email->fresh() ?? $email;

        return [
            'status' => $result,
            'email' => $fresh,
            'to' => (string) $fresh->to_email,
            'available_at' => $fresh->available_at,
        ];
    }

    /**
     * @return 'sent'|'queued'|'failed'
     */
    public function process(OutboundEmail $email): string
    {
        $email = $email->fresh() ?? $email;

        if ($email->status === OutboundEmail::STATUS_SENT) {
            return 'sent';
        }

        if ($email->status === OutboundEmail::STATUS_FAILED) {
            return 'failed';
        }

        if ($email->available_at !== null && $email->available_at->isFuture()) {
            return 'queued';
        }

        if (MailSendingQuota::isBlocked()) {
            $availableAt = MailSendingQuota::availableAt();
            $email->update([
                'available_at' => $availableAt,
                'last_error' => 'Waiting for email sending quota to reset.',
            ]);

            return 'queued';
        }

        $email->update([
            'attempts' => ((int) $email->attempts) + 1,
            'last_error' => null,
        ]);

        try {
            $this->dispatch($email);

            $email->update([
                'status' => OutboundEmail::STATUS_SENT,
                'sent_at' => now(),
                'available_at' => null,
                'last_error' => null,
            ]);

            return 'sent';
        } catch (Throwable $e) {
            if (MailSendingQuota::isExceeded($e)) {
                $availableAt = MailSendingQuota::markExceeded($e);
                $email->update([
                    'status' => OutboundEmail::STATUS_PENDING,
                    'available_at' => $availableAt,
                    'last_error' => $e->getMessage(),
                ]);

                Log::warning('Outbound email deferred until mail quota resets', [
                    'outbound_email_id' => $email->id,
                    'type' => $email->type,
                    'to' => $email->to_email,
                    'available_at' => $availableAt->toIso8601String(),
                ]);

                return 'queued';
            }

            // Bounces / hard SMTP rejects: fail once. Do not retry — retries burn quota.
            if (MailSendingQuota::isPermanentFailure($e)) {
                $email->update([
                    'status' => OutboundEmail::STATUS_FAILED,
                    'available_at' => null,
                    'last_error' => $e->getMessage(),
                ]);

                Log::error('Outbound email permanently failed (no retry)', [
                    'outbound_email_id' => $email->id,
                    'type' => $email->type,
                    'to' => $email->to_email,
                    'message' => $e->getMessage(),
                ]);

                return 'failed';
            }

            // Other unexpected errors: one short retry window only, then stop.
            $attempts = (int) $email->fresh()?->attempts;
            $failed = $attempts >= 2;

            $email->update([
                'status' => $failed ? OutboundEmail::STATUS_FAILED : OutboundEmail::STATUS_PENDING,
                'available_at' => $failed ? null : now()->addMinutes(30),
                'last_error' => $e->getMessage(),
            ]);

            Log::error('Outbound email send failed', [
                'outbound_email_id' => $email->id,
                'type' => $email->type,
                'to' => $email->to_email,
                'attempts' => $attempts,
                'message' => $e->getMessage(),
            ]);

            return $failed ? 'failed' : 'queued';
        }
    }

    public function processDue(int $limit = 1): int
    {
        $processed = 0;

        OutboundEmail::query()
            ->due()
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get()
            ->each(function (OutboundEmail $email) use (&$processed): void {
                $this->process($email);
                $processed++;
            });

        return $processed;
    }

    private function dispatch(OutboundEmail $email): void
    {
        $payload = is_array($email->payload) ? $email->payload : [];

        match ($email->type) {
            OutboundEmail::TYPE_CAR_PARK_TICKETS => app(SendCarParkTicketsEmail::class)->execute(
                array_values(array_map('intval', $payload['registration_ids'] ?? [])),
                (string) $email->to_email,
                isset($payload['note']) && is_string($payload['note']) && trim($payload['note']) !== ''
                    ? trim($payload['note'])
                    : null,
            ),
            OutboundEmail::TYPE_CANCELLATION => app(SendTicketCancellationEmail::class)->execute(
                toEmail: (string) $email->to_email,
                ticketNumber: (string) ($payload['ticket_number'] ?? ''),
                congregation: (string) ($payload['congregation'] ?? ''),
                driverName: (string) ($payload['driver_name'] ?? ''),
            ),
            OutboundEmail::TYPE_CHANGE_DECLINE => app(SendTicketChangeRequestDeclinedEmail::class)->execute(
                toEmail: (string) $email->to_email,
                requesterName: (string) ($payload['requester_name'] ?? ''),
                congregation: (string) ($payload['congregation'] ?? ''),
            ),
            OutboundEmail::TYPE_HOTEL_DECLINE => app(SendHotelGuestParkingDeclinedEmail::class)->execute(
                toEmail: (string) $email->to_email,
                requesterName: (string) ($payload['requester_name'] ?? ''),
            ),
            default => throw new \InvalidArgumentException('Unknown outbound email type: '.$email->type),
        };
    }
}
