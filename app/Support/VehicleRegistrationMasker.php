<?php

declare(strict_types=1);

namespace App\Support;

final class VehicleRegistrationMasker
{
    /**
     * Lightly mask a vehicle registration for public lists, e.g. "HG12ABC" → "HG1*ABC".
     * Enough to discourage casual scanning while still identifiable to the owner.
     */
    public static function mask(?string $registration): string
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', trim((string) $registration)) ?? '');
        if ($normalized === '') {
            return '';
        }

        $length = strlen($normalized);
        if ($length <= 4) {
            return $normalized;
        }

        // Keep first 3 and last 3; mask only the middle character(s).
        $keepStart = 3;
        $keepEnd = 3;
        $middle = max(1, $length - $keepStart - $keepEnd);

        return substr($normalized, 0, $keepStart)
            .str_repeat('*', $middle)
            .substr($normalized, -$keepEnd);
    }
}
