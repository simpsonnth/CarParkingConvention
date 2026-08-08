<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\HotelGuestParkingRequest;
use App\Models\Setting;

final class TicketEmailBody
{
    public const SETTING_KEY = 'ticket_email_body';

    public const DEFAULT = "Please find attached {{count}} car park ticket PDF(s) for {{congregation}}.\n\nEach attachment is named after the registrant.\n\nKind regards,\nConvention Parking";

    public static function template(): string
    {
        $stored = Setting::get(self::SETTING_KEY);

        if ($stored === null || trim((string) $stored) === '') {
            return self::DEFAULT;
        }

        return (string) $stored;
    }

    public static function renderHtml(int $ticketCount, string $congregationLabel): string
    {
        $congregation = $congregationLabel !== '' ? $congregationLabel : 'selected registrations';

        $text = str_replace(
            ['{{count}}', '{{congregation}}'],
            [(string) $ticketCount, $congregation],
            self::template(),
        );

        $paragraphs = preg_split("/\R{2,}/", trim($text)) ?: [];

        $html = '';

        if (self::isRadissonHotelGuestCongregation($congregationLabel)) {
            $printHint = trim((string) __('mail.radisson_ticket_print_hint'));
            if ($printHint !== '') {
                $html .= '<p style="margin:0 0 1.25em;padding:12px 14px;border:2px solid #111827;border-radius:6px;background:#fef3c7;color:#111827;font-size:16px;line-height:1.45;">'
                    .'<strong style="font-size:17px;">'.e($printHint).'</strong>'
                    .'</p>';
            }
        }

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            $html .= '<p>'.nl2br(e($paragraph)).'</p>';
        }

        return $html !== '' ? $html : '<p>'.e(self::DEFAULT).'</p>';
    }

    public static function isRadissonHotelGuestCongregation(string $congregationLabel): bool
    {
        return mb_strtolower(trim($congregationLabel)) === mb_strtolower(HotelGuestParkingRequest::CONGREGATION_NAME);
    }
}
