<?php

declare(strict_types=1);

namespace App\Support;

use App\Actions\Registrations\SendCarParkTicketsEmail;
use App\Actions\TicketChangeRequests\SendTicketCancellationEmail;
use Illuminate\Support\Facades\Log;

final class DeferredTicketMail
{
    /**
     * Run immediately in tests; after the HTTP response in production so Livewire
     * can return success before slow PDF generation finishes.
     */
    public static function sendCarParkTickets(array $registrationIds, string $toEmail): void
    {
        $send = function () use ($registrationIds, $toEmail): void {
            try {
                app(SendCarParkTicketsEmail::class)->execute($registrationIds, $toEmail);
            } catch (\Throwable $e) {
                Log::error('Deferred car park ticket email failed', [
                    'to' => $toEmail,
                    'registration_ids' => $registrationIds,
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

    public static function sendCancellation(
        string $toEmail,
        string $ticketNumber,
        string $congregation,
        string $driverName,
    ): void {
        $send = function () use ($toEmail, $ticketNumber, $congregation, $driverName): void {
            try {
                app(SendTicketCancellationEmail::class)->execute(
                    toEmail: $toEmail,
                    ticketNumber: $ticketNumber,
                    congregation: $congregation,
                    driverName: $driverName,
                );
            } catch (\Throwable $e) {
                Log::error('Deferred cancellation email failed', [
                    'to' => $toEmail,
                    'ticket_number' => $ticketNumber,
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
