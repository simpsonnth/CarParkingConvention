<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OutboundEmail;
use App\Services\OutboundEmailProcessor;
use Illuminate\Support\Facades\Log;

final class DeferredTicketMail
{
    /**
     * Persist the outbound email, then attempt delivery after the HTTP response
     * (or immediately in tests). Quota failures stay queued until Resend resets.
     *
     * @param  list<int>  $registrationIds
     */
    public static function sendCarParkTickets(
        array $registrationIds,
        string $toEmail,
        ?string $note = null,
    ): void {
        $processor = app(OutboundEmailProcessor::class);
        $email = $processor->enqueue(
            OutboundEmail::TYPE_CAR_PARK_TICKETS,
            $toEmail,
            [
                'registration_ids' => array_values(array_unique(array_map('intval', $registrationIds))),
                'note' => $note,
            ],
        );

        self::runAfterResponse($email->id);
    }

    public static function sendCancellation(
        string $toEmail,
        string $ticketNumber,
        string $congregation,
        string $driverName,
    ): void {
        $processor = app(OutboundEmailProcessor::class);
        $email = $processor->enqueue(
            OutboundEmail::TYPE_CANCELLATION,
            $toEmail,
            [
                'ticket_number' => $ticketNumber,
                'congregation' => $congregation,
                'driver_name' => $driverName,
            ],
        );

        self::runAfterResponse($email->id);
    }

    public static function sendDecline(
        string $toEmail,
        string $requesterName,
        string $congregation,
    ): void {
        $processor = app(OutboundEmailProcessor::class);
        $email = $processor->enqueue(
            OutboundEmail::TYPE_CHANGE_DECLINE,
            $toEmail,
            [
                'requester_name' => $requesterName,
                'congregation' => $congregation,
            ],
        );

        self::runAfterResponse($email->id);
    }

    public static function sendHotelGuestParkingDecline(
        string $toEmail,
        string $requesterName,
    ): void {
        $processor = app(OutboundEmailProcessor::class);
        $email = $processor->enqueue(
            OutboundEmail::TYPE_HOTEL_DECLINE,
            $toEmail,
            [
                'requester_name' => $requesterName,
            ],
        );

        self::runAfterResponse($email->id);
    }

    private static function runAfterResponse(int $outboundEmailId): void
    {
        $send = function () use ($outboundEmailId): void {
            try {
                $processor = app(OutboundEmailProcessor::class);
                $email = OutboundEmail::query()->find($outboundEmailId);
                if ($email !== null) {
                    $processor->process($email);
                }
                // Keep opportunistic drain tiny on the web PHP process — Chrome PDF
                // generation is memory-heavy on small VPS hosts.
                $processor->processDue(1);
            } catch (\Throwable $e) {
                Log::error('Deferred outbound email runner failed', [
                    'outbound_email_id' => $outboundEmailId,
                    'message' => $e->getMessage(),
                ]);
            }
        };

        if (app()->runningUnitTests()) {
            $send();

            return;
        }

        dispatch($send)->afterResponse();
    }
}
