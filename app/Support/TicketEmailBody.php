<?php

declare(strict_types=1);

namespace App\Support;

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
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            $html .= '<p>'.nl2br(e($paragraph)).'</p>';
        }

        return $html !== '' ? $html : '<p>'.e(self::DEFAULT).'</p>';
    }
}
