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
    public const CACHE_KEY_PREFIX = 'mail_provider_quota_blocked_until:';

    /** @deprecated Kept for older cache entries during deploy. */
    public const CACHE_KEY = 'mail_sending_quota_blocked_until';

    /**
     * @return list<string>
     */
    public static function mailersInPreferenceOrder(): array
    {
        $primary = (string) config('mail.transactional_primary', config('mail.default', 'smtp'));
        $failover = (string) config('mail.transactional_failover', 'brevo');
        $tertiary = (string) config('mail.transactional_tertiary', 'mailersend');

        $order = [];
        foreach ([$primary, $failover, $tertiary] as $mailer) {
            $mailer = trim($mailer);
            if ($mailer !== '' && ! in_array($mailer, $order, true)) {
                $order[] = $mailer;
            }
        }

        return $order;
    }

    public static function isExceeded(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'daily email sending quota')
            || str_contains($message, 'daily_quota_exceeded')
            || str_contains($message, 'monthly_quota_exceeded')
            || str_contains($message, 'monthly email sending quota')
            || str_contains($message, 'quota exceeded')
            || str_contains($message, 'send limit exceeded')
            || str_contains($message, 'send limit reached')
            || str_contains($message, 'account credits are 0')
            || str_contains($message, 'plan limits')
            || str_contains($message, 'too many requests')
            || str_contains($message, 'rate limit');
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

    public static function isProviderBlocked(string $mailer): bool
    {
        $cached = Cache::get(self::CACHE_KEY_PREFIX.$mailer);
        if (is_string($cached) && $cached !== '' && Carbon::parse($cached)->isFuture()) {
            return true;
        }

        // Legacy single-key block (pre-failover) only applies to primary/smtp/resend.
        if (in_array($mailer, ['smtp', 'resend', 'resend_smtp', (string) config('mail.transactional_primary')], true)) {
            $legacy = Cache::get(self::CACHE_KEY);
            if (is_string($legacy) && $legacy !== '' && Carbon::parse($legacy)->isFuture()) {
                return true;
            }
        }

        return false;
    }

    public static function hasAvailableProvider(): bool
    {
        foreach (self::mailersInPreferenceOrder() as $mailer) {
            if (! self::mailerConfigured($mailer)) {
                continue;
            }
            if (! self::isProviderBlocked($mailer)) {
                return true;
            }
        }

        return false;
    }

    public static function mailerConfigured(string $mailer): bool
    {
        $config = config("mail.mailers.{$mailer}");
        if (! is_array($config) || ($config['transport'] ?? null) === null) {
            return false;
        }

        if (($config['transport'] ?? '') === 'smtp') {
            $host = trim((string) ($config['host'] ?? ''));
            $password = trim((string) ($config['password'] ?? ''));

            return $host !== '' && $password !== '';
        }

        if (($config['transport'] ?? '') === 'resend') {
            return trim((string) config('services.resend.key', '')) !== '';
        }

        if (($config['transport'] ?? '') === 'mailersend') {
            return trim((string) config('mailersend-driver.api_key', '')) !== '';
        }

        return true;
    }

    /**
     * True only when every configured transactional provider is quota-blocked.
     */
    public static function isBlocked(): bool
    {
        return ! self::hasAvailableProvider();
    }

    public static function availableAt(?string $mailer = null): CarbonInterface
    {
        if ($mailer !== null) {
            $cached = Cache::get(self::CACHE_KEY_PREFIX.$mailer);
            if (is_string($cached) && $cached !== '') {
                return Carbon::parse($cached);
            }

            return self::nextUtcReset();
        }

        $times = [];
        foreach (self::mailersInPreferenceOrder() as $name) {
            if (! self::mailerConfigured($name)) {
                continue;
            }
            $cached = Cache::get(self::CACHE_KEY_PREFIX.$name);
            if (is_string($cached) && $cached !== '') {
                $times[] = Carbon::parse($cached);
            }
        }

        $legacy = Cache::get(self::CACHE_KEY);
        if (is_string($legacy) && $legacy !== '') {
            $times[] = Carbon::parse($legacy);
        }

        if ($times === []) {
            return self::nextUtcReset();
        }

        return collect($times)->sort()->first() ?? self::nextUtcReset();
    }

    public static function markProviderExceeded(string $mailer, ?Throwable $e = null): CarbonInterface
    {
        $availableAt = self::availableAtFromException($e) ?? self::nextUtcReset();

        Cache::put(
            self::CACHE_KEY_PREFIX.$mailer,
            $availableAt->toIso8601String(),
            $availableAt->copy()->addHour(),
        );

        // Keep legacy key in sync when the primary provider trips, so older
        // process checks still behave during rolling deploys.
        $primary = (string) config('mail.transactional_primary', 'smtp');
        if ($mailer === $primary || in_array($mailer, ['smtp', 'resend', 'resend_smtp'], true)) {
            Cache::put(self::CACHE_KEY, $availableAt->toIso8601String(), $availableAt->copy()->addHour());
        }

        if (! self::hasAvailableProvider()) {
            OutboundEmail::query()
                ->where('status', OutboundEmail::STATUS_PENDING)
                ->where(function ($q) use ($availableAt): void {
                    $q->whereNull('available_at')
                        ->orWhere('available_at', '<', $availableAt);
                })
                ->update([
                    'available_at' => $availableAt,
                    'last_error' => $e?->getMessage() ?? 'Email sending quota reached on all providers.',
                ]);
        } else {
            // Failover is available — clear artificial deferrals so jobs send now.
            OutboundEmail::query()
                ->where('status', OutboundEmail::STATUS_PENDING)
                ->whereNotNull('available_at')
                ->where('available_at', '>', now())
                ->update([
                    'available_at' => null,
                    'last_error' => 'Provider quota reached; next failover available.',
                ]);
        }

        return $availableAt;
    }

    /**
     * @deprecated Use markProviderExceeded()
     */
    public static function markExceeded(?Throwable $e = null): CarbonInterface
    {
        $primary = (string) config('mail.transactional_primary', config('mail.default', 'smtp'));

        return self::markProviderExceeded($primary, $e);
    }

    public static function clear(?string $mailer = null): void
    {
        if ($mailer !== null) {
            Cache::forget(self::CACHE_KEY_PREFIX.$mailer);

            return;
        }

        Cache::forget(self::CACHE_KEY);
        foreach (self::mailersInPreferenceOrder() as $name) {
            Cache::forget(self::CACHE_KEY_PREFIX.$name);
        }
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
            return now('UTC')->startOfMonth()->addMonth()->addMinutes(5)->timezone(config('app.timezone'));
        }

        return self::nextUtcReset();
    }
}
