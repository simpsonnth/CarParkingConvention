<?php

declare(strict_types=1);

namespace App\Actions\Extras;

use App\Models\CarPark;
use App\Models\ParkingExtra;
use App\Models\ParkingRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertExtraToRegistration
{
    /**
     * Convert a pending parking extra into a real registration with an assigned car park.
     * Bypasses congregation survey quotas (same as admin registration create).
     */
    public function execute(ParkingExtra $extra, int $carParkId, ?User $actor = null): ParkingRegistration
    {
        if (! $extra->isPending()) {
            throw ValidationException::withMessages([
                'actionCarParkId' => __('extras.already_actioned'),
            ]);
        }

        if (! CarPark::query()->whereKey($carParkId)->exists()) {
            throw ValidationException::withMessages([
                'actionCarParkId' => __('extras.car_park_required'),
            ]);
        }

        return DB::transaction(function () use ($extra, $carParkId, $actor): ParkingRegistration {
            /** @var ParkingExtra $locked */
            $locked = ParkingExtra::query()->whereKey($extra->id)->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'actionCarParkId' => __('extras.already_actioned'),
                ]);
            }

            $vehicleReg = $locked->vehicle_type === 'car' && filled($locked->vehicle_registration)
                ? strtoupper(str_replace(' ', '', trim((string) $locked->vehicle_registration)))
                : ($locked->vehicle_registration ? trim((string) $locked->vehicle_registration) : null);

            $registration = ParkingRegistration::query()->create([
                'name' => $locked->name,
                'congregation' => $locked->congregation,
                'car_park_id' => $carParkId,
                'vehicle_type' => $locked->vehicle_type ?: 'car',
                'vehicle_registration' => $vehicleReg,
                'contact_number' => $locked->contact_number,
                'email' => $locked->email,
                'elderly_infirm_parking' => (bool) $locked->elderly_infirm_parking,
                'days' => $locked->days ?? [],
                'sharing_with_other_congregations' => false,
                'sharing_congregations_notes' => null,
                'coach_captain_to_be_assigned' => false,
                'is_circuit_overseer' => false,
            ]);

            $locked->update([
                'status' => ParkingExtra::STATUS_ACTIONED,
                'parking_registration_id' => $registration->id,
                'actioned_at' => now(),
                'actioned_by' => $actor?->id,
            ]);

            return $registration;
        });
    }
}
