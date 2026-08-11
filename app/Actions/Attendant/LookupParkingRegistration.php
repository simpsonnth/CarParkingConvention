<?php

declare(strict_types=1);

namespace App\Actions\Attendant;

use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Services\ParkingRegistrationDuplicateSignals;
use Illuminate\Support\Collection;

final class LookupParkingRegistration
{
    public const MAX_RESULTS = 5;

    public function __construct(
        private readonly ParkingRegistrationDuplicateSignals $signals,
    ) {}

    /**
     * Look up active parking registrations by ticket number (digit-only) or vehicle plate.
     *
     * @return list<array{
     *     id: int,
     *     ticket_number: string,
     *     vehicle_registration: ?string,
     *     name: string,
     *     contact_number: string,
     *     email: ?string,
     *     congregation: string,
     *     vehicle_type: string,
     *     is_circuit_overseer: bool,
     *     car_park_name: ?string,
     *     car_park_color: ?string,
     *     car_park_is_individual: bool,
     *     can_check_in: bool
     * }>
     */
    public function execute(string $query): array
    {
        $term = trim($query);
        if ($term === '') {
            return [];
        }

        $registrations = $this->findRegistrations($term);

        return $registrations
            ->take(self::MAX_RESULTS)
            ->map(fn (ParkingRegistration $registration): array => $this->toResult($registration))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, ParkingRegistration>
     */
    private function findRegistrations(string $term): Collection
    {
        if (ctype_digit($term)) {
            $registration = ParkingRegistration::query()
                ->with('carPark')
                ->find((int) $term);

            return $registration !== null
                ? collect([$registration])
                : collect();
        }

        $normalized = $this->signals->normalizeVehicleRegistration($term);
        if ($normalized === null) {
            return collect();
        }

        return ParkingRegistration::query()
            ->with('carPark')
            ->where('vehicle_registration', $normalized)
            ->orderBy('id')
            ->limit(self::MAX_RESULTS)
            ->get();
    }

    /**
     * @return array{
     *     id: int,
     *     ticket_number: string,
     *     vehicle_registration: ?string,
     *     name: string,
     *     contact_number: string,
     *     email: ?string,
     *     congregation: string,
     *     vehicle_type: string,
     *     is_circuit_overseer: bool,
     *     car_park_name: ?string,
     *     car_park_color: ?string,
     *     car_park_is_individual: bool,
     *     can_check_in: bool
     * }
     */
    private function toResult(ParkingRegistration $registration): array
    {
        $effective = $this->resolveEffectiveCarPark($registration);

        return [
            'id' => $registration->id,
            'ticket_number' => $registration->ticketNumber(),
            'vehicle_registration' => $registration->vehicle_registration,
            'name' => (string) $registration->name,
            'contact_number' => (string) $registration->contact_number,
            'email' => $registration->email,
            'congregation' => (string) $registration->congregation,
            'vehicle_type' => (string) ($registration->vehicle_type ?: 'car'),
            'is_circuit_overseer' => (bool) $registration->is_circuit_overseer,
            'car_park_name' => $effective['name'],
            'car_park_color' => $effective['color'],
            'car_park_is_individual' => $effective['is_individual'],
            'can_check_in' => ! (bool) $registration->is_circuit_overseer,
        ];
    }

    /**
     * @return array{name: ?string, color: ?string, is_individual: bool}
     */
    private function resolveEffectiveCarPark(ParkingRegistration $registration): array
    {
        if ($registration->car_park_id) {
            $park = $registration->carPark ?? CarPark::query()->find($registration->car_park_id);
            if ($park !== null) {
                return [
                    'name' => $park->name,
                    'color' => $park->color,
                    'is_individual' => true,
                ];
            }
        }

        $congregation = Congregation::query()
            ->with('carPark')
            ->whereRaw('TRIM(name) = TRIM(?)', [$registration->congregation])
            ->first();

        $park = $congregation?->carPark;

        return [
            'name' => $park?->name,
            'color' => $park?->color,
            'is_individual' => false,
        ];
    }
}
