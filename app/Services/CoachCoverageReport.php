<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;

final class CoachCoverageReport
{
    /**
     * Compare expected spreadsheet congregations against coach parking registrations.
     *
     * An expected congregation is covered when it has its own coach registration,
     * or when its survey lists a sharing partner that has a coach registration.
     *
     * @return array{
     *     expected_total: int,
     *     registered_expected: int,
     *     missing: list<string>,
     *     unexpected: list<string>,
     *     registered_names: list<string>,
     *     covered_via_sharing: list<string>
     * }
     */
    public function summarize(): array
    {
        /** @var list<string> $expected */
        $expected = array_values(array_filter(
            array_map(
                static fn (mixed $name): string => trim((string) $name),
                config('expected_coach_congregations', [])
            ),
            static fn (string $name): bool => $name !== ''
        ));

        $registeredRaw = ParkingRegistration::query()
            ->where('vehicle_type', 'coach')
            ->pluck('congregation')
            ->map(static fn (mixed $name): string => trim((string) $name))
            ->filter(static fn (string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();

        /** @var array<string, string> $registeredByKey display name keyed by normalized name */
        $registeredByKey = [];
        foreach ($registeredRaw as $name) {
            $registeredByKey[$this->normalize($name)] = $name;
        }

        /** @var array<string, int> $nameToId */
        $nameToId = [];
        Congregation::query()
            ->get(['id', 'name'])
            ->each(function (Congregation $congregation) use (&$nameToId): void {
                $key = $this->normalize((string) $congregation->name);
                if ($key === '') {
                    return;
                }
                $nameToId[$key] = (int) $congregation->id;
            });

        /** @var array<int, true> $coachCongregationIds */
        $coachCongregationIds = [];
        foreach ($registeredByKey as $key => $_display) {
            if (isset($nameToId[$key])) {
                $coachCongregationIds[$nameToId[$key]] = true;
            }
        }

        $responses = CongregationNumbersResponse::query()
            ->get(['congregation_id', 'sharing_coach_with_others', 'shared_with_congregation_ids'])
            ->keyBy('congregation_id');

        $missing = [];
        $coveredViaSharing = [];
        $registeredExpected = 0;

        foreach ($expected as $name) {
            $key = $this->normalize($name);
            if (isset($registeredByKey[$key])) {
                $registeredExpected++;

                continue;
            }

            $congregationId = $nameToId[$key] ?? null;
            if ($congregationId !== null && $this->isCoveredViaSharing(
                $congregationId,
                $responses,
                $coachCongregationIds
            )) {
                $registeredExpected++;
                $coveredViaSharing[] = $name;

                continue;
            }

            $missing[] = $name;
        }

        /** @var array<string, true> $expectedKeys */
        $expectedKeys = [];
        foreach ($expected as $name) {
            $expectedKeys[$this->normalize($name)] = true;
        }

        $unexpected = [];
        foreach ($registeredByKey as $key => $displayName) {
            if (! isset($expectedKeys[$key])) {
                $unexpected[] = $displayName;
            }
        }

        sort($unexpected, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'expected_total' => count($expected),
            'registered_expected' => $registeredExpected,
            'missing' => $missing,
            'unexpected' => $unexpected,
            'registered_names' => array_values($registeredByKey),
            'covered_via_sharing' => $coveredViaSharing,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, CongregationNumbersResponse>  $responses
     * @param  array<int, true>  $coachCongregationIds
     */
    private function isCoveredViaSharing(
        int $congregationId,
        $responses,
        array $coachCongregationIds
    ): bool {
        $response = $responses->get($congregationId);
        if ($response === null || ! ($response->sharing_coach_with_others ?? false)) {
            return false;
        }

        foreach ($response->normalizedSharedCongregationIds() as $partnerId) {
            if ($partnerId === $congregationId) {
                continue;
            }

            if (isset($coachCongregationIds[$partnerId])) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $name): string
    {
        return mb_strtolower(trim($name), 'UTF-8');
    }
}
