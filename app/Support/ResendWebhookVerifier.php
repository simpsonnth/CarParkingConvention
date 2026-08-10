<?php

declare(strict_types=1);

namespace App\Support;

use UnexpectedValueException;

final class ResendWebhookVerifier
{
    /**
     * Verify a Resend/Svix webhook signature.
     *
     * @param  array<string, string|null>  $headers
     */
    public static function verify(string $payload, array $headers, string $secret): void
    {
        $id = trim((string) ($headers['svix-id'] ?? $headers['Svix-Id'] ?? ''));
        $timestamp = trim((string) ($headers['svix-timestamp'] ?? $headers['Svix-Timestamp'] ?? ''));
        $signatureHeader = trim((string) ($headers['svix-signature'] ?? $headers['Svix-Signature'] ?? ''));

        if ($id === '' || $timestamp === '' || $signatureHeader === '') {
            throw new UnexpectedValueException('Missing Svix webhook headers.');
        }

        if (! ctype_digit($timestamp)) {
            throw new UnexpectedValueException('Invalid Svix timestamp.');
        }

        // Reject timestamps older/newer than 5 minutes.
        if (abs(time() - (int) $timestamp) > 300) {
            throw new UnexpectedValueException('Svix timestamp outside tolerance.');
        }

        $secretBytes = self::secretBytes($secret);
        $signedContent = $id.'.'.$timestamp.'.'.$payload;
        $expected = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

        $passed = false;
        foreach (preg_split('/\s+/', $signatureHeader) ?: [] as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            [, $signature] = array_pad(explode(',', $part, 2), 2, '');
            $signature = trim($signature);
            if ($signature !== '' && hash_equals($expected, $signature)) {
                $passed = true;
                break;
            }
        }

        if (! $passed) {
            throw new UnexpectedValueException('Invalid Svix webhook signature.');
        }
    }

    private static function secretBytes(string $secret): string
    {
        $secret = trim($secret);
        if (str_starts_with($secret, 'whsec_')) {
            $decoded = base64_decode(substr($secret, 6), true);
            if ($decoded === false) {
                throw new UnexpectedValueException('Invalid webhook secret encoding.');
            }

            return $decoded;
        }

        return $secret;
    }
}
