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

    public static function renderHtml(int $ticketCount, string $congregationLabel, ?string $note = null): string
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

        $note = $note !== null ? trim($note) : '';
        if ($note !== '') {
            $label = trim((string) __('mail.ticket_note_label'));
            $html .= '<p style="margin:1.25em 0 0;padding:12px 14px;border:1px solid #d4d4d8;border-radius:6px;background:#fafafa;color:#18181b;font-size:15px;line-height:1.5;">'
                .($label !== '' ? '<strong>'.e($label).'</strong><br>' : '')
                .nl2br(e($note))
                .'</p>';
        }

        return $html !== '' ? $html : '<p>'.e(self::DEFAULT).'</p>';
    }

    public static function isRadissonHotelGuestCongregation(string $congregationLabel): bool
    {
        return mb_strtolower(trim($congregationLabel)) === mb_strtolower(HotelGuestParkingRequest::CONGREGATION_NAME);
    }
}
