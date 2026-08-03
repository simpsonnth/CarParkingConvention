<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use Illuminate\Support\Collection;

final class CoachCoverageReport
{
    /**
     * Compare expected spreadsheet congregations against coach parking registrations.
     *
     * An expected congregation is covered when it has its own coach registration,
     * or when it mutually shares (survey) with a congregation that has a coach registration.
     *
     * @return array{
     *     expected_total: int,
     *     registered_expected: int,
     *     missing: list<string>,
     *     unexpected: list<string>,
     *     registered_names: list<string>,
     *     covered_via_sharing: list<array{name: string, partners: list<string>}>
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
        /** @var array<int, string> $idToName */
        $idToName = [];
        Congregation::query()
            ->get(['id', 'name'])
            ->each(function (Congregation $congregation) use (&$nameToId, &$idToName): void {
                $key = $this->normalize((string) $congregation->name);
                if ($key === '') {
                    return;
                }
                $id = (int) $congregation->id;
                $nameToId[$key] = $id;
                $idToName[$id] = (string) $congregation->name;
            });

        /** @var array<int, true> $coachCongregationIds */
        $coachCongregationIds = [];
        foreach ($registeredByKey as $key => $_display) {
            if (isset($nameToId[$key])) {
                $coachCongregationIds[$nameToId[$key]] = true;
            }
        }

        /** @var Collection<int|string, CongregationNumbersResponse> $responses */
        $responses = CongregationNumbersResponse::query()
            ->get(['congregation_id', 'sharing_coach_with_others', 'shared_with_congregation_ids'])
            ->keyBy('congregation_id');

        $missing = [];
        /** @var list<array{name: string, partners: list<string>}> $coveredViaSharing */
        $coveredViaSharing = [];
        $registeredExpected = 0;

        foreach ($expected as $name) {
            $key = $this->normalize($name);
            if (isset($registeredByKey[$key])) {
                $registeredExpected++;

                continue;
            }

            $congregationId = $nameToId[$key] ?? null;
            $coveringPartners = $congregationId === null
                ? []
                : $this->coveringPartnerNames($congregationId, $responses, $coachCongregationIds, $idToName);

            if ($coveringPartners !== []) {
                $registeredExpected++;
                $coveredViaSharing[] = [
                    'name' => $name,
                    'partners' => $coveringPartners,
                ];

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
     * Partner congregations that both appear in this congregation's survey sharing
     * list and mutually list this congregation back, and that hold a coach registration.
     *
     * @param  Collection<int|string, CongregationNumbersResponse>  $responses
     * @param  array<int, true>  $coachCongregationIds
     * @param  array<int, string>  $idToName
     * @return list<string>
     */
    private function coveringPartnerNames(
        int $congregationId,
        Collection $responses,
        array $coachCongregationIds,
        array $idToName
    ): array {
        $response = $responses->get($congregationId);
        if ($response === null || ! ($response->sharing_coach_with_others ?? false)) {
            return [];
        }

        $partners = [];
        foreach ($response->normalizedSharedCongregationIds() as $partnerId) {
            if ($partnerId === $congregationId || ! isset($coachCongregationIds[$partnerId])) {
                continue;
            }

            $other = $responses->get($partnerId);
            if ($other === null || ! ($other->sharing_coach_with_others ?? false)) {
                continue;
            }

            if (! in_array($congregationId, $other->normalizedSharedCongregationIds(), true)) {
                continue;
            }

            $partners[] = $idToName[$partnerId] ?? ('#'.$partnerId);
        }

        sort($partners, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values(array_unique($partners));
    }

    private function normalize(string $name): string
    {
        return mb_strtolower(trim($name), 'UTF-8');
    }
}
