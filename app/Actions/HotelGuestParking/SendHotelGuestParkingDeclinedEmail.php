<?php

declare(strict_types=1);

namespace App\Actions\HotelGuestParking;

use App\Mail\HotelGuestParkingRequestDeclinedMail;
use App\Support\TicketEmailCcList;
use App\Support\TransactionalMail;
use Illuminate\Validation\ValidationException;

class SendHotelGuestParkingDeclinedEmail
{
    public function execute(string $toEmail, string $requesterName): array
    {
        $to = strtolower(trim($toEmail));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'decline' => __('management.hotel_guest_parking.decline_email_required'),
            ]);
        }

        $cc = array_values(array_filter(
            TicketEmailCcList::all(),
            fn (string $email): bool => $email !== $to,
        ));

        $result = TransactionalMail::send(new HotelGuestParkingRequestDeclinedMail(
            recipientEmail: $to,
            requesterName: $requesterName,
            ccAddresses: $cc,
        ), $to);

        return [
            'to' => $to,
            'cc' => $cc,
            'mailer' => $result['mailer'],
        ];
    }
}
