<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use Illuminate\Support\Facades\DB;

final class ParkingRegistrationQuotaValidator
{
    public function __construct(
        private readonly ParkingRegistrationDuplicateSignals $duplicateSignals,
    ) {}

    /**
     * @return array{standard_car_used: int, disabled_used: int, coach_exists: bool}
     */
    public function countsForCongregationLabel(string $congregationLabel, ?int $excludeRegistrationId = null): array
    {
        $base = ParkingRegistration::query()
            ->whereRaw('TRIM(congregation) = ?', [trim($congregationLabel)])
            ->when($excludeRegistrationId !== null, fn ($q) => $q->where('id', '!=', $excludeRegistrationId));

        $standardCarUsed = (clone $base)
            ->where('vehicle_type', 'car')
            ->where('elderly_infirm_parking', false)
            ->count();

        $disabledUsed = (clone $base)
            ->where('vehicle_type', 'car')
            ->where('elderly_infirm_parking', true)
            ->count();

        $coachExists = (clone $base)
            ->where('vehicle_type', 'coach')
            ->exists();

        return [
            'standard_car_used' => $standardCarUsed,
            'disabled_used' => $disabledUsed,
            'coach_exists' => $coachExists,
        ];
    }

    /**
     * Quota gate for registration forms (hide fields when full).
     *
     * @return array{hide_remaining_fields: bool, no_survey: bool, allocation_full: bool}
     */
    public function congregationQuotaGate(Congregation $congregation): array
    {
        $resp = CongregationNumbersResponse::query()
            ->where('congregation_id', $congregation->id)
            ->first();

        if ($resp === null) {
            return [
                'hide_remaining_fields' => true,
                'no_survey' => true,
                'allocation_full' => false,
            ];
        }

        $label = trim((string) $congregation->name);
        $counts = $this->countsForCongregationLabel($label);

        $carTicketLimit = (int) $resp->car_park_tickets_count;
        $totalCarsUsed = $counts['standard_car_used'] + $counts['disabled_used'];

        if ($resp->disabled_parking_required) {
            $standardRoom = $counts['standard_car_used'] < $carTicketLimit;
            $disabledLimit = (int) ($resp->disabled_parking_count ?? 0);
            $disabledRoom = $disabledLimit > 0 && $counts['disabled_used'] < $disabledLimit;
        } else {
            $standardRoom = $totalCarsUsed < $carTicketLimit;
            $disabledRoom = false;
        }

        $coachRoom = $resp->organizes_coach && ! $counts['coach_exists'];

        if ($standardRoom || $disabledRoom || $coachRoom) {
            return [
                'hide_remaining_fields' => false,
                'no_survey' => false,
                'allocation_full' => false,
            ];
        }

        return [
            'hide_remaining_fields' => true,
            'no_survey' => false,
            'allocation_full' => true,
        ];
    }

    /**
     * Validate quota and duplicate rules for create or update.
     *
     * @return array{0: string, 1: string}|null [field, message]
     */
    public function validateRegistration(
        Congregation $congregation,
        string $vehicleType,
        bool $elderlyInfirmParking,
        ?string $formattedVehicleReg,
        ?int $excludeRegistrationId = null,
        ?ParkingRegistration $existingRegistration = null,
    ): ?array {
        return DB::transaction(function () use (
            $congregation,
            $vehicleType,
            $elderlyInfirmParking,
            $formattedVehicleReg,
            $excludeRegistrationId,
            $existingRegistration,
        ): ?array {
            $resp = CongregationNumbersResponse::query()
                ->where('congregation_id', $congregation->id)
                ->lockForUpdate()
                ->first();

            if ($resp === null) {
                return ['congregationCode', __('register.quota_no_survey')];
            }

            $congregationLabel = trim((string) $congregation->name);
            $counts = $this->countsForCongregationLabel($congregationLabel, $excludeRegistrationId);
            $standardCarUsed = $counts['standard_car_used'];
            $disabledUsed = $counts['disabled_used'];
            $coachExists = $counts['coach_exists'];

            if ($vehicleType === 'car') {
                if ($elderlyInfirmParking) {
                    $disabledLimit = $resp->disabled_parking_required
                      ? (int) ($resp->disabled_parking_count ?? 0)
                      : 0;

                    if ($disabledLimit <= 0) {
                        $grandfathered = $existingRegistration !== null
                            && $existingRegistration->elderly_infirm_parking
                            && $elderlyInfirmParking;

                        if (! $grandfathered) {
                            return ['elderlyInfirmParking', __('register.quota_disabled_not_requested', [
                                'congregation' => $congregation->name,
                            ])];
                        }
                    }

                    if ($disabledUsed >= $disabledLimit) {
                        return ['elderlyInfirmParking', __('register.quota_disabled_full', [
                            'limit' => $disabledLimit,
                            'congregation' => $congregation->name,
                        ])];
                    }
                } else {
                    $standardLimit = (int) $resp->car_park_tickets_count;
                    $carsUsedTowardTicketCap = $resp->disabled_parking_required
                      ? $standardCarUsed
                      : ($standardCarUsed + $disabledUsed);

                    if ($carsUsedTowardTicketCap >= $standardLimit) {
                        return ['congregationCode', __('register.quota_car_full', [
                            'limit' => $standardLimit,
                            'congregation' => $congregation->name,
                        ])];
                    }
                }
            } else {
                if (! $resp->organizes_coach) {
                    return ['vehicleType', __('register.quota_coach_not_organised')];
                }
                if ($coachExists) {
                    return ['vehicleType', __('register.quota_coach_taken')];
                }
            }

            if ($formattedVehicleReg !== null) {
                $existingByReg = $this->duplicateSignals->findActiveByNormalizedVehicleReg($formattedVehicleReg);
                if ($existingByReg !== null && $existingByReg->id !== $excludeRegistrationId) {
                    return ['vehicleReg', __('register.duplicate_vehicle_registration', [
                        'name' => $existingByReg->name,
                        'congregation' => $existingByReg->congregation ?: '—',
                    ])];
                }
            }

            return null;
        });
    }
}
