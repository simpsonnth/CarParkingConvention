<?php

namespace App\Services;

use App\Models\ParkingRegistration;
use Illuminate\Support\Facades\DB;

/**
 * Normalization and duplicate detection for parking registrations.
 *
 * Vehicle registration is stored uppercased without spaces; the same rules apply here.
 * Shared plate (two drivers): second signup is intentionally blocked — see public form UX.
 */
final class ParkingRegistrationDuplicateSignals
{
    public function normalizeVehicleRegistration(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $trimmed = trim($input);

        return $trimmed === ''
            ? null
            : strtoupper(str_replace(' ', '', $trimmed));
    }

    public function normalizeEmailForLookup(string $email): string
    {
        return strtolower(trim($email));
    }

    public function findActiveByNormalizedVehicleReg(?string $normalized): ?ParkingRegistration
    {
        if ($normalized === null || $normalized === '') {
            return null;
        }

        return ParkingRegistration::query()
            ->where('vehicle_registration', $normalized)
            ->orderBy('id')
            ->first();
    }

    public function findActiveByNormalizedEmail(string $email): ?ParkingRegistration
    {
        $normalized = $this->normalizeEmailForLookup($email);
        if ($normalized === '') {
            return null;
        }

        return ParkingRegistration::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalized])
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array<string, true> normalized email => true when 2+ active rows share that email
     */
    public function duplicateNormalizedEmailKeys(): array
    {
        $keys = ParkingRegistration::query()
            ->selectRaw('LOWER(TRIM(email)) as dup_key')
            ->groupBy(DB::raw('LOWER(TRIM(email))'))
            ->havingRaw('COUNT(*) > 1')
            ->pluck('dup_key')
            ->filter(fn ($k) => $k !== null && $k !== '')
            ->all();

        return array_fill_keys($keys, true);
    }

    /**
     * @return array<string, true> stored vehicle_registration => true when 2+ active rows share it
     */
    public function duplicateNormalizedVehicleRegKeys(): array
    {
        $keys = ParkingRegistration::query()
            ->whereNotNull('vehicle_registration')
            ->where('vehicle_registration', '!=', '')
            ->select('vehicle_registration')
            ->groupBy('vehicle_registration')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('vehicle_registration')
            ->all();

        return array_fill_keys($keys, true);
    }
}
