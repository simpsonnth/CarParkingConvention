<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use Illuminate\Support\Collection;

final class CoachRegistrationMetrics
{
    /**
     * @return array{registrations_total: int, unique_coaches: int}
     */
    public function summarize(): array
    {
        $registrations = ParkingRegistration::query()
            ->where('vehicle_type', 'coach')
            ->get(['id', 'congregation']);

        $registrationsTotal = $registrations->count();

        if ($registrationsTotal === 0) {
            return [
                'registrations_total' => 0,
                'unique_coaches' => 0,
            ];
        }

        /** @var array<string, int> $nameToId */
        $nameToId = [];
        Congregation::query()
            ->get(['id', 'name'])
            ->each(function (Congregation $congregation) use (&$nameToId): void {
                $key = mb_strtolower(trim((string) $congregation->name), 'UTF-8');
                if ($key !== '') {
                    $nameToId[$key] = (int) $congregation->id;
                }
            });

        /** @var array<int, true> $coachCongregationIds */
        $coachCongregationIds = [];
        $unmatchedRegistrations = 0;

        foreach ($registrations as $registration) {
            $key = mb_strtolower(trim((string) $registration->congregation), 'UTF-8');
            if ($key !== '' && isset($nameToId[$key])) {
                $coachCongregationIds[$nameToId[$key]] = true;
            } else {
                $unmatchedRegistrations++;
            }
        }

        $ids = array_keys($coachCongregationIds);

        if ($ids === []) {
            return [
                'registrations_total' => $registrationsTotal,
                'unique_coaches' => $unmatchedRegistrations,
            ];
        }

        $responses = CongregationNumbersResponse::query()
            ->whereIn('congregation_id', $ids)
            ->get(['congregation_id', 'shared_with_congregation_ids']);

        return [
            'registrations_total' => $registrationsTotal,
            'unique_coaches' => $this->countUniqueCoachComponents($ids, $responses) + $unmatchedRegistrations,
        ];
    }

    /**
     * @param  list<int>  $congregationIds
     * @param  Collection<int, CongregationNumbersResponse>  $responses
     */
    private function countUniqueCoachComponents(array $congregationIds, Collection $responses): int
    {
        /** @var array<int, int> $parent */
        $parent = [];
        foreach ($congregationIds as $id) {
            $parent[$id] = $id;
        }

        $byCongregationId = $responses->keyBy('congregation_id');
        $idSet = array_fill_keys($congregationIds, true);

        foreach ($responses as $response) {
            $a = (int) $response->congregation_id;
            foreach ($response->normalizedSharedCongregationIds() as $b) {
                if ($a === $b || ! isset($idSet[$b])) {
                    continue;
                }

                $other = $byCongregationId->get($b);
                if ($other === null) {
                    continue;
                }

                if (! in_array($a, $other->normalizedSharedCongregationIds(), true)) {
                    continue;
                }

                $this->unionByRoot($parent, $a, $b);
            }
        }

        $roots = [];
        foreach ($congregationIds as $id) {
            $roots[$this->findRoot($parent, $id)] = true;
        }

        return count($roots);
    }

    /**
     * @param  array<int, int>  $parent
     */
    private function findRoot(array &$parent, int $x): int
    {
        if ($parent[$x] !== $x) {
            $parent[$x] = $this->findRoot($parent, $parent[$x]);
        }

        return $parent[$x];
    }

    /**
     * @param  array<int, int>  $parent
     */
    private function unionByRoot(array &$parent, int $a, int $b): void
    {
        $ra = $this->findRoot($parent, $a);
        $rb = $this->findRoot($parent, $b);

        if ($ra !== $rb) {
            $parent[$ra] = $rb;
        }
    }
}
