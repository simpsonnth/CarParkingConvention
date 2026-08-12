<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CarPark;

final class NearestCarParkResolver
{
    /**
     * @return array{id: int, name: string, distance_meters: float}|null
     */
    public function resolve(float $latitude, float $longitude): ?array
    {
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

        return $nearest;
    }
}
