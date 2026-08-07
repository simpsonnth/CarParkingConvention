<?php

declare(strict_types=1);

namespace App\Support;

final class PersonNameMasker
{
    /**
     * Mask a personal name for public display, e.g. "Nathan Simpson" → "N****n S*****n".
     */
    public static function mask(?string $name): string
    {
        $trimmed = trim((string) $name);
        if ($trimmed === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $trimmed) ?: [];
        $masked = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $masked[] = self::maskWord($part);
        }

        return implode(' ', $masked);
    }

    private static function maskWord(string $word): string
    {
        $length = mb_strlen($word);
        if ($length === 1) {
            return $word;
        }

        if ($length === 2) {
            return mb_substr($word, 0, 1).'*';
        }

        $first = mb_substr($word, 0, 1);
        $last = mb_substr($word, -1);
        $stars = str_repeat('*', $length - 2);

        return $first.$stars.$last;
    }
}
