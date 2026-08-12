<?php

declare(strict_types=1);

namespace App\Actions\HotelGuestParking;

use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Support\VehicleRegistrationNormalizer;

class LookupRadissonParkingStatus
{
    /**
     * Public-safe lookup by vehicle registration: existence + effective car park name only.
     *
     * @return array{found: bool, car_park_name: ?string}
     */
    public function handle(string $vehicleRegistrationInput): array
    {
        $normalized = VehicleRegistrationNormalizer::normalize($vehicleRegistrationInput, 'car');
        if ($normalized === null || $normalized === '') {
            return [
                'found' => false,
                'car_park_name' => null,
            ];
        }

        $registration = ParkingRegistration::query()
            ->whereRaw("REPLACE(UPPER(COALESCE(vehicle_registration, '')), ' ', '') = ?", [$normalized])
            ->orderBy('id')
            ->first();

        if ($registration === null) {
            return [
                'found' => false,
                'car_park_name' => null,
            ];
        }

        return [
            'found' => true,
            'car_park_name' => $this->resolveEffectiveCarParkName($registration),
        ];
    }

    private function resolveEffectiveCarParkName(ParkingRegistration $registration): ?string
    {
        if ($registration->car_park_id) {
            $park = $registration->carPark ?? CarPark::query()->find($registration->car_park_id);
            if ($park !== null) {
                return $park->name;
            }
        }

        $congregation = Congregation::query()
            ->with('carPark')
            ->whereRaw('TRIM(name) = TRIM(?)', [$registration->congregation])
            ->first();

        return $congregation?->carPark?->name;
    }
}
