<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * MailerSend Free plan caps daily API requests (100). Each email send counts.
 * GET /v1/api-quota does not count toward the limit — use it before sending.
 */
final class MailerSendApiQuota
{
    public const CACHE_REMAINING_KEY = 'mailersend_api_quota_remaining';

    public const CACHE_RESET_KEY = 'mailersend_api_quota_reset';

    public static function apiKey(): string
    {
        return trim((string) config('mailersend-driver.api_key', config('services.mailersend.key', '')));
    }

    public static function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    /**
     * Keep this many requests unused so the Free plan daily cap is never hit.
     */
    public static function reserve(): int
    {
        return max(0, (int) config('services.mailersend.reserve', 5));
    }

    /**
     * Remaining API requests today, or null when the key is missing / lookup failed.
     */
    public static function remaining(?bool $fresh = false): ?int
    {
        if (! self::isConfigured()) {
            return null;
        }

        if (! $fresh) {
            $cached = Cache::get(self::CACHE_REMAINING_KEY);
            if (is_int($cached)) {
                return $cached;
            }
        }

        try {
            $response = Http::timeout(8)
                ->withToken(self::apiKey())
                ->acceptJson()
                ->get('https://api.mailersend.com/v1/api-quota');

            if (! $response->successful()) {
                Log::warning('MailerSend API quota check failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $remaining = $response->json('remaining');
            if (! is_numeric($remaining)) {
                return null;
            }

            $remaining = (int) $remaining;
            $reset = $response->json('reset');
            $ttl = now()->addMinutes(2);

            if (is_string($reset) && $reset !== '') {
                try {
                    $resetAt = Carbon::parse($reset);
                    Cache::put(self::CACHE_RESET_KEY, $resetAt->toIso8601String(), $resetAt->copy()->addHour());
                    if ($resetAt->isFuture()) {
                        $ttl = $resetAt;
                    }
                } catch (Throwable) {
                    // Keep short TTL when reset parsing fails.
                }
            }

            Cache::put(self::CACHE_REMAINING_KEY, $remaining, $ttl);

            return $remaining;
        } catch (Throwable $e) {
            Log::warning('MailerSend API quota check exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function resetsAt(): ?CarbonInterface
    {
        $cached = Cache::get(self::CACHE_RESET_KEY);
        if (is_string($cached) && $cached !== '') {
            return Carbon::parse($cached);
        }

        // Populate cache from a fresh lookup when possible.
        self::remaining(true);
        $cached = Cache::get(self::CACHE_RESET_KEY);
        if (is_string($cached) && $cached !== '') {
            return Carbon::parse($cached);
        }

        return null;
    }

    public static function hasCapacity(): bool
    {
        $remaining = self::remaining();

        // If we cannot check, do not block sending (fail open).
        if ($remaining === null) {
            return true;
        }

        return $remaining > self::reserve();
    }

    /**
     * After a successful MailerSend send, reduce the cached remaining count.
     */
    public static function recordSpend(int $requests = 1): void
    {
        $cached = Cache::get(self::CACHE_REMAINING_KEY);
        if (! is_int($cached)) {
            self::forgetCache();

            return;
        }

        Cache::put(
            self::CACHE_REMAINING_KEY,
            max(0, $cached - max(1, $requests)),
            Cache::get(self::CACHE_RESET_KEY)
                ? Carbon::parse((string) Cache::get(self::CACHE_RESET_KEY))
                : now()->addMinutes(2),
        );
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_REMAINING_KEY);
        Cache::forget(self::CACHE_RESET_KEY);
    }
}
