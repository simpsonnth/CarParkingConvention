<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CarPark;

final class NearestCarParkResolver
{
    /** Max distance (metres) to treat a check-in pin as near a car park. */
    public const MAX_NEAR_METERS = 1000.0;

    /**
     * @return array{id: int, name: string, distance_meters: float}|null
     */
    public function resolve(
        float $latitude,
        float $longitude,
        float $maxMeters = self::MAX_NEAR_METERS,
    ): ?array {
        $nearest = null;
        $nearestDistance = null;

        $parks = CarPark::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'name', 'latitude', 'longitude']);

        foreach ($parks as $park) {
            $distance = GeoCoordinates::distanceMeters(
                $latitude,
                $longitude,
                (float) $park->latitude,
                (float) $park->longitude,
            );

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearest = [
                    'id' => (int) $park->id,
                    'name' => (string) $park->name,
                    'distance_meters' => $distance,
                ];
            }
        }

        if ($nearest === null || $nearestDistance === null || $nearestDistance > $maxMeters) {
            return null;
        }

        return $nearest;
    }
}
