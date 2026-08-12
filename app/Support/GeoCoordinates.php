<?php

declare(strict_types=1);

namespace App\Support;

final class GeoCoordinates
{
    public static function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 7, '.', ''), '0'), '.');
    }

    public static function distanceMeters(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): float {
        $earthRadiusMeters = 6371000.0;

        $latFrom = deg2rad($fromLatitude);
        $latTo = deg2rad($toLatitude);
        $latDelta = deg2rad($toLatitude - $fromLatitude);
        $lngDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * (sin($lngDelta / 2) ** 2);

        return 2 * $earthRadiusMeters * asin(min(1, sqrt($a)));
    }
}
