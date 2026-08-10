<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Brevo's free SMTP relay can accept messages (250) even when the account
 * send limit is exhausted, so Laravel marks mail as sent while Brevo drops it.
 * Check remaining credits via the account API before using the Brevo mailer.
 */
final class BrevoSendCredits
{
    public const CACHE_KEY = 'brevo_send_credits_remaining';

    public static function apiKey(): string
    {
        return trim((string) config('services.brevo.key', ''));
    }

    public static function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    /**
     * Remaining send credits, or null when the API key is missing / lookup failed.
     */
    public static function remaining(?bool $fresh = false): ?int
    {
        if (! self::isConfigured()) {
            return null;
        }

        if (! $fresh) {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_int($cached)) {
                return $cached;
            }
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'api-key' => self::apiKey(),
                    'accept' => 'application/json',
                ])
                ->get('https://api.brevo.com/v3/account');

            if (! $response->successful()) {
                Log::warning('Brevo account credit check failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $credits = null;
            foreach ((array) $response->json('plan', []) as $plan) {
                if (! is_array($plan)) {
                    continue;
                }
                if (($plan['creditsType'] ?? null) === 'sendLimit' && array_key_exists('credits', $plan)) {
                    $credits = (int) $plan['credits'];
                    break;
                }
            }

            if ($credits === null && is_numeric($response->json('credits'))) {
                $credits = (int) $response->json('credits');
            }

            if ($credits === null) {
                return null;
            }

            Cache::put(self::CACHE_KEY, $credits, now()->addMinutes(2));

            return $credits;
        } catch (Throwable $e) {
            Log::warning('Brevo account credit check exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function hasCredits(): bool
    {
        $remaining = self::remaining();

        // If we cannot check, do not block SMTP (fail open).
        if ($remaining === null) {
            return true;
        }

        return $remaining > 0;
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
