<?php

declare(strict_types=1);

namespace App\Actions\Attendant;

use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
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
     *     can_check_in: bool,
     *     is_parked: bool,
     *     parked_pass_id: ?int,
     *     clocked_in_at: ?string,
     *     parked_car_park_name: ?string,
     *     parked_check_in_maps_url: ?string,
     *     parked_check_in_google_maps_url: ?string,
     *     parked_check_in_apple_maps_url: ?string,
     *     parked_gps_closest_car_park_name: ?string
     * }>
     */
    public function execute(string $query): array
    {
        $term = trim($query);
        if ($term === '') {
            return [];
        }

        $registrations = $this->findRegistrations($term)->take(self::MAX_RESULTS);
        $parkedByPlate = $this->findParkedPassesByPlate($registrations);

        return $registrations
            ->map(fn (ParkingRegistration $registration): array => $this->toResult(
                $registration,
                $this->parkedPassForRegistration($registration, $parkedByPlate),
            ))
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
     * @param  Collection<int, ParkingRegistration>  $registrations
     * @return array<string, ParkingPass>
     */
    private function findParkedPassesByPlate(Collection $registrations): array
    {
        $plates = $registrations
            ->pluck('vehicle_registration')
            ->map(fn ($plate) => $this->signals->normalizeVehicleRegistration(is_string($plate) ? $plate : null))
            ->filter()
            ->unique()
            ->values();

        if ($plates->isEmpty()) {
            return [];
        }

        $passes = ParkingPass::query()
            ->parkedToday()
            ->with('carPark')
            ->where(function ($query) use ($plates): void {
                foreach ($plates as $plate) {
                    $query->orWhereRaw(
                        "REPLACE(UPPER(COALESCE(vehicle_reg, '')), ' ', '') = ?",
                        [$plate]
                    );
                }
            })
            ->orderByDesc('scanned_at')
            ->get();

        $byPlate = [];
        foreach ($passes as $pass) {
            $key = $this->signals->normalizeVehicleRegistration($pass->vehicle_reg);
            if ($key === null || isset($byPlate[$key])) {
                continue;
            }

            $byPlate[$key] = $pass;
        }

        return $byPlate;
    }

    /**
     * @param  array<string, ParkingPass>  $parkedByPlate
     */
    private function parkedPassForRegistration(ParkingRegistration $registration, array $parkedByPlate): ?ParkingPass
    {
        $key = $this->signals->normalizeVehicleRegistration($registration->vehicle_registration);

        return $key !== null ? ($parkedByPlate[$key] ?? null) : null;
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
     *     can_check_in: bool,
     *     is_parked: bool,
     *     parked_pass_id: ?int,
     *     clocked_in_at: ?string,
     *     parked_car_park_name: ?string,
     *     parked_check_in_maps_url: ?string,
     *     parked_check_in_google_maps_url: ?string,
     *     parked_check_in_apple_maps_url: ?string,
     *     parked_gps_closest_car_park_name: ?string
     * }
     */
    private function toResult(ParkingRegistration $registration, ?ParkingPass $parkedPass): array
    {
        $effective = $this->resolveEffectiveCarPark($registration);
        $isParked = $parkedPass !== null;
        $isCircuitOverseer = (bool) $registration->is_circuit_overseer;
        $googleMapsUrl = $parkedPass?->checkInGoogleMapsUrl();
        $appleMapsUrl = $parkedPass?->checkInAppleMapsUrl();

        return [
            'id' => $registration->id,
            'ticket_number' => $registration->ticketNumber(),
            'vehicle_registration' => $registration->vehicle_registration,
            'name' => (string) $registration->name,
            'contact_number' => (string) $registration->contact_number,
            'email' => $registration->email,
            'congregation' => (string) $registration->congregation,
            'vehicle_type' => (string) ($registration->vehicle_type ?: 'car'),
            'is_circuit_overseer' => $isCircuitOverseer,
            'car_park_name' => $effective['name'],
            'car_park_color' => $effective['color'],
            'car_park_is_individual' => $effective['is_individual'],
            'can_check_in' => ! $isCircuitOverseer && ! $isParked,
            'is_parked' => $isParked,
            'parked_pass_id' => $parkedPass?->id,
            'clocked_in_at' => $parkedPass?->scanned_at?->timezone(config('app.timezone'))->format('H:i'),
            'parked_car_park_name' => $parkedPass?->carPark?->name,
            'parked_check_in_maps_url' => $googleMapsUrl,
            'parked_check_in_google_maps_url' => $googleMapsUrl,
            'parked_check_in_apple_maps_url' => $appleMapsUrl,
            'parked_gps_closest_car_park_name' => $parkedPass?->closestCheckInCarParkName(),
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
