<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OutboundEmail;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class MailSendingQuota
{
    public const CACHE_KEY = 'mail_sending_quota_blocked_until';

    public static function isExceeded(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'daily email sending quota')
            || str_contains($message, 'daily_quota_exceeded')
            || str_contains($message, 'monthly_quota_exceeded')
            || str_contains($message, 'monthly email sending quota');
    }

    /**
     * Hard delivery failures / bounce-style SMTP rejects must not be retried
     * (they would burn provider quota without ever succeeding).
     */
    public static function isPermanentFailure(Throwable $e): bool
    {
        if (self::isExceeded($e)) {
            return false;
        }

        $message = strtolower($e->getMessage());

        foreach ([
            'mailbox unavailable',
            'user unknown',
            'unknown user',
            'no such user',
            'recipient rejected',
            'address rejected',
            'invalid address',
            'invalid recipient',
            'does not exist',
            'undeliverable',
            'bounced',
            'bounce',
            '550 5.',
            '551 5.',
            '552 5.',
            '553 5.',
            '554 5.',
            'relay not permitted',
            'spam message rejected',
        ] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        // Generic 55x without quota wording is treated as permanent.
        return (bool) preg_match('/\b55[0-4]\b/', $message);
    }

    public static function availableAt(): CarbonInterface
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return Carbon::parse($cached);
        }

        // Resend free-tier daily quota resets at midnight UTC.
        return now('UTC')->addDay()->startOfDay()->addMinutes(5)->timezone(config('app.timezone'));
    }

    public static function isBlocked(): bool
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (! is_string($cached) || $cached === '') {
            return false;
        }

        return Carbon::parse($cached)->isFuture();
    }

    public static function markExceeded(?Throwable $e = null): CarbonInterface
    {
        $availableAt = self::availableAtFromException($e) ?? self::nextUtcReset();

        Cache::put(self::CACHE_KEY, $availableAt->toIso8601String(), $availableAt->copy()->addHour());

        OutboundEmail::query()
            ->where('status', OutboundEmail::STATUS_PENDING)
            ->where(function ($q) use ($availableAt): void {
                $q->whereNull('available_at')
                    ->orWhere('available_at', '<', $availableAt);
            })
            ->update([
                'available_at' => $availableAt,
                'last_error' => $e?->getMessage() ?? 'Daily email sending quota reached.',
            ]);

        return $availableAt;
    }

    public static function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function nextUtcReset(): CarbonInterface
    {
        return now('UTC')->addDay()->startOfDay()->addMinutes(5)->timezone(config('app.timezone'));
    }

    private static function availableAtFromException(?Throwable $e): ?CarbonInterface
    {
        if ($e === null) {
            return null;
        }

        $message = strtolower($e->getMessage());
        if (str_contains($message, 'monthly')) {
            // Wait until the 1st of next month 00:05 UTC for monthly caps.
            return now('UTC')->startOfMonth()->addMonth()->addMinutes(5)->timezone(config('app.timezone'));
        }

        return self::nextUtcReset();
    }
}
