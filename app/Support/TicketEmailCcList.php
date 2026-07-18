<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

final class TicketEmailCcList
{
    public const SETTING_KEY = 'ticket_email_ccs';

    public const DEFAULT_CC = 'nathan-simpson@outlook.com';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $raw = Setting::get(self::SETTING_KEY);

        if ($raw === null || trim((string) $raw) === '') {
            return [self::DEFAULT_CC];
        }

        return self::parse((string) $raw);
    }

    /**
     * @return list<string>
     */
    public static function parse(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $emails = [];

        foreach ($parts as $part) {
            $email = strtolower(trim($part));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $emails[$email] = $email;
        }

        return array_values($emails);
    }

    public static function toStorageString(string $raw): string
    {
        return implode("\n", self::parse($raw));
    }
}
