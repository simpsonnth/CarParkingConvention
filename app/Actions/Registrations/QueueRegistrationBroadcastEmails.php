<?php

declare(strict_types=1);

namespace App\Actions\Registrations;

use App\Models\OutboundEmail;
use App\Models\ParkingRegistration;
use App\Services\OutboundEmailProcessor;
use App\Support\MailSendingQuota;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QueueRegistrationBroadcastEmails
{
    /** Seconds between each queued send attempt in a batch. */
    private const STAGGER_SECONDS = 2;

    public function __construct(
        private readonly OutboundEmailProcessor $outbound,
    ) {}

    /**
     * @param  list<int>  $registrationIds
     * @return array{batch_id: string, recipient_count: int, queued: int}
     */
    public function handle(array $registrationIds, string $subject, string $body): array
    {
        $subject = trim($subject);
        $body = trim($body);

        if ($subject === '' || $body === '') {
            throw ValidationException::withMessages([
                'broadcastSubject' => __('registrations.broadcast_subject_body_required'),
            ]);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $registrationIds))));
        if ($ids === []) {
            throw ValidationException::withMessages([
                'broadcastScope' => __('registrations.broadcast_no_recipients'),
            ]);
        }

        $recipients = $this->uniqueRecipients(
            ParkingRegistration::query()->whereIn('id', $ids)
        );

        if ($recipients === []) {
            throw ValidationException::withMessages([
                'broadcastScope' => __('registrations.broadcast_no_recipients'),
            ]);
        }

        $batchId = (string) Str::uuid();
        $baseAvailableAt = MailSendingQuota::isBlocked()
            ? (MailSendingQuota::availableAt() ?? now())
            : now();

        $queued = 0;
        foreach (array_values($recipients) as $index => $recipient) {
            // Keep tests deterministic (no future available_at); stagger in production for rate limits.
            $availableAt = app()->runningUnitTests()
                ? null
                : Carbon::parse($baseAvailableAt)->addSeconds($index * self::STAGGER_SECONDS);

            $this->outbound->enqueue(
                OutboundEmail::TYPE_REGISTRATION_BROADCAST,
                $recipient['email'],
                [
                    'subject' => $subject,
                    'body' => $body,
                    'name' => $recipient['name'],
                    'registration_id' => $recipient['registration_id'],
                    'broadcast_batch_id' => $batchId,
                ],
                $availableAt,
            );
            $queued++;
        }

        $this->kickProcessor();

        return [
            'batch_id' => $batchId,
            'recipient_count' => count($recipients),
            'queued' => $queued,
        ];
    }

    /**
     * @return list<array{email: string, name: string, registration_id: int}>
     */
    public function uniqueRecipients(Builder $query): array
    {
        $rows = (clone $query)
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->get();

        $byEmail = [];
        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row->email ?? '')));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (isset($byEmail[$email])) {
                continue;
            }

            $byEmail[$email] = [
                'email' => $email,
                'name' => trim((string) ($row->name ?? '')),
                'registration_id' => (int) $row->id,
            ];
        }

        return array_values($byEmail);
    }

    private function kickProcessor(): void
    {
        $run = function (): void {
            try {
                app(OutboundEmailProcessor::class)->processDue(3);
            } catch (\Throwable $e) {
                Log::error('Registration broadcast kick failed', [
                    'message' => $e->getMessage(),
                ]);
            }
        };

        if (app()->runningUnitTests()) {
            // Drain the whole batch in tests (stagger is disabled above).
            app(OutboundEmailProcessor::class)->processDue(100);

            return;
        }

        dispatch($run)->afterResponse();
    }
}
