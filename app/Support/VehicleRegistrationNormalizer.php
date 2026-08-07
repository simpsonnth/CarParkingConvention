<?php

declare(strict_types=1);

namespace App\Support;

final class VehicleRegistrationNormalizer
{
    public static function normalize(?string $value, string $vehicleType = 'car'): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if ($vehicleType === 'car') {
            return strtoupper(str_replace(' ', '', $trimmed));
        }

        return $trimmed;
    }

    public static function matches(?string $left, ?string $right, string $vehicleType = 'car'): bool
    {
        $a = self::normalize($left, $vehicleType) ?? '';
        $b = self::normalize($right, $vehicleType) ?? '';

        return $a !== '' && strcasecmp($a, $b) === 0;
    }
}
