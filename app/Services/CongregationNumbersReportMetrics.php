<?php

namespace App\Services;

use App\Models\CircuitOverseerParkingRequirement;
use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CongregationNumbersReportMetrics
{
    /**
     * @return array{
     *     total_car_park_tickets: int,
     *     co_total_car_park_tickets: int,
     *     combined_total_car_park_tickets: int,
     *     total_disabled_spaces: int,
     *     co_disabled_required_count: int,
     *     combined_disabled_demand: int,
     *     co_row_count: int,
     *     congregations_total: int,
     *     congregations_submitted: int,
     *     congregations_missing: int,
     *     responses_count: int,
     *     submission_rate_percent: float,
     *     average_car_park_tickets_per_response: float,
     *     coach_organizers_raw: int,
     *     coach_organizers_deduped_components: int,
     *     highlight_max_tickets: array{name: string, tickets: int, congregation_uuid: string|null}|null,
     *     highlight_max_disabled: array{name: string, spaces: int, congregation_uuid: string|null}|null,
     *     highlight_co_max_tickets: array{name: string, tickets: int}|null,
     *     top_by_car_park_tickets: list<array{name: string, tickets: int, congregation_uuid: string|null}>,
     *     top_by_disabled_spaces: list<array{name: string, spaces: int, congregation_uuid: string|null}>,
     *     recent_submissions: list<array{name: string, congregation_uuid: string|null, updated_at: string, updated_at_display: string}>,
     *     co_top_by_car_park_tickets: list<array{name: string, tickets: int, disabled_required: bool}>,
     *     co_recent: list<array{name: string, updated_at: string, updated_at_display: string}>,
     * }
     */
    public function compute(): array
    {
        $responsesCount = (int) CongregationNumbersResponse::query()->count();

        $totalCarParkTickets = (int) CongregationNumbersResponse::query()->sum('car_park_tickets_count');

        $coTotalCarParkTickets = (int) CircuitOverseerParkingRequirement::query()->sum('car_park_tickets_count');
        $combinedTotalCarParkTickets = $totalCarParkTickets + $coTotalCarParkTickets;

        $coRowCount = (int) CircuitOverseerParkingRequirement::query()->count();
        $coDisabledRequiredCount = (int) CircuitOverseerParkingRequirement::query()
            ->where('disabled_parking_required', true)
            ->count();

        $totalDisabledSpaces = (int) CongregationNumbersResponse::query()
            ->where('disabled_parking_required', true)
            ->sum(DB::raw('COALESCE(disabled_parking_count, 0)'));

        $combinedDisabledDemand = $totalDisabledSpaces + $coDisabledRequiredCount;

        $congregationsTotal = Congregation::query()->count();
        $congregationsSubmitted = Congregation::query()->whereHas('numbersResponse')->count();
        $congregationsMissing = max(0, $congregationsTotal - $congregationsSubmitted);

        $submissionRatePercent = $congregationsTotal > 0
            ? round(100 * $congregationsSubmitted / $congregationsTotal, 1)
            : 0.0;

        $averageCarParkTicketsPerResponse = $responsesCount > 0
            ? round($totalCarParkTickets / $responsesCount, 1)
            : 0.0;

        $organizerResponses = CongregationNumbersResponse::query()
            ->where('organizes_coach', true)
            ->get(['id', 'congregation_id', 'shared_with_congregation_ids']);

        $coachOrganizersRaw = $organizerResponses->count();
        $deduped = $this->countCoachOrganizerComponents($organizerResponses);

        $topCarPark = $this->topByCarParkTickets(5);
        $topDisabled = $this->topByDisabledSpaces(5);

        $highlightMaxTicketsRow = $topCarPark->first(fn (array $row): bool => $row['tickets'] > 0);
        $highlightMaxTickets = $highlightMaxTicketsRow !== null
            ? [
                'name' => $highlightMaxTicketsRow['name'],
                'tickets' => $highlightMaxTicketsRow['tickets'],
                'congregation_uuid' => $highlightMaxTicketsRow['congregation_uuid'],
            ]
            : null;

        $highlightMaxDisabledRow = $topDisabled->first(fn (array $row): bool => $row['spaces'] > 0);
        $highlightMaxDisabled = $highlightMaxDisabledRow !== null
            ? [
                'name' => $highlightMaxDisabledRow['name'],
                'spaces' => $highlightMaxDisabledRow['spaces'],
                'congregation_uuid' => $highlightMaxDisabledRow['congregation_uuid'],
            ]
            : null;

        $coTopTickets = CircuitOverseerParkingRequirement::query()
            ->orderByDesc('car_park_tickets_count')
            ->limit(5)
            ->get()
            ->map(fn (CircuitOverseerParkingRequirement $r): array => [
                'name' => $r->first_name,
                'tickets' => (int) $r->car_park_tickets_count,
                'disabled_required' => (bool) $r->disabled_parking_required,
            ]);

        $highlightCoMaxTicketsRow = $coTopTickets->first(fn (array $row): bool => $row['tickets'] > 0);
        $highlightCoMaxTickets = $highlightCoMaxTicketsRow !== null
            ? [
                'name' => $highlightCoMaxTicketsRow['name'],
                'tickets' => $highlightCoMaxTicketsRow['tickets'],
            ]
            : null;

        $coRecent = CircuitOverseerParkingRequirement::query()
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function (CircuitOverseerParkingRequirement $r): array {
                $at = $r->updated_at;
                $tz = config('app.timezone');
                $iso = $at instanceof Carbon ? $at->copy()->timezone($tz)->toIso8601String() : '';
                $display = $at instanceof Carbon
                    ? $at->copy()->timezone($tz)->format('M j, Y g:i a')
                    : '—';

                return [
                    'name' => $r->first_name,
                    'updated_at' => $iso,
                    'updated_at_display' => $display,
                ];
            })
            ->values()
            ->all();

        $recentSubmissions = CongregationNumbersResponse::query()
            ->with(['congregation:id,name,uuid'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function (CongregationNumbersResponse $r): array {
                $at = $r->updated_at;
                $tz = config('app.timezone');
                $iso = $at instanceof Carbon ? $at->copy()->timezone($tz)->toIso8601String() : '';
                $display = $at instanceof Carbon
                    ? $at->copy()->timezone($tz)->format('M j, Y g:i a')
                    : '—';

                return [
                    'name' => $r->congregation?->name ?? '—',
                    'congregation_uuid' => $r->congregation?->uuid,
                    'updated_at' => $iso,
                    'updated_at_display' => $display,
                ];
            })
            ->values()
            ->all();

        return [
            'total_car_park_tickets' => $totalCarParkTickets,
            'co_total_car_park_tickets' => $coTotalCarParkTickets,
            'combined_total_car_park_tickets' => $combinedTotalCarParkTickets,
            'total_disabled_spaces' => $totalDisabledSpaces,
            'co_disabled_required_count' => $coDisabledRequiredCount,
            'combined_disabled_demand' => $combinedDisabledDemand,
            'co_row_count' => $coRowCount,
            'congregations_total' => $congregationsTotal,
            'congregations_submitted' => $congregationsSubmitted,
            'congregations_missing' => $congregationsMissing,
            'responses_count' => $responsesCount,
            'submission_rate_percent' => $submissionRatePercent,
            'average_car_park_tickets_per_response' => $averageCarParkTicketsPerResponse,
            'coach_organizers_raw' => $coachOrganizersRaw,
            'coach_organizers_deduped_components' => $deduped,
            'highlight_max_tickets' => $highlightMaxTickets,
            'highlight_max_disabled' => $highlightMaxDisabled,
            'highlight_co_max_tickets' => $highlightCoMaxTickets,
            'top_by_car_park_tickets' => $topCarPark->values()->all(),
            'top_by_disabled_spaces' => $topDisabled->values()->all(),
            'recent_submissions' => $recentSubmissions,
            'co_top_by_car_park_tickets' => $coTopTickets->values()->all(),
            'co_recent' => $coRecent,
        ];
    }

    /**
     * @return Collection<int, array{name: string, tickets: int, congregation_uuid: string|null}>
     */
    private function topByCarParkTickets(int $limit): Collection
    {
        return CongregationNumbersResponse::query()
            ->with(['congregation:id,name,uuid'])
            ->orderByDesc('car_park_tickets_count')
            ->limit($limit)
            ->get()
            ->map(fn (CongregationNumbersResponse $r): array => [
                'name' => $r->congregation?->name ?? '—',
                'tickets' => (int) $r->car_park_tickets_count,
                'congregation_uuid' => $r->congregation?->uuid,
            ]);
    }

    /**
     * @return Collection<int, array{name: string, spaces: int, congregation_uuid: string|null}>
     */
    private function topByDisabledSpaces(int $limit): Collection
    {
        return CongregationNumbersResponse::query()
            ->where('disabled_parking_required', true)
            ->with(['congregation:id,name,uuid'])
            ->orderByDesc(DB::raw('COALESCE(disabled_parking_count, 0)'))
            ->limit($limit)
            ->get()
            ->map(fn (CongregationNumbersResponse $r): array => [
                'name' => $r->congregation?->name ?? '—',
                'spaces' => (int) ($r->disabled_parking_count ?? 0),
                'congregation_uuid' => $r->congregation?->uuid,
            ]);
    }

    /**
     * @param  Collection<int, CongregationNumbersResponse>  $organizerResponses
     */
    private function countCoachOrganizerComponents(Collection $organizerResponses): int
    {
        $byCongregationId = $organizerResponses->keyBy('congregation_id');
        $organizerIds = $organizerResponses->pluck('congregation_id')->unique()->values()->all();

        if ($organizerIds === []) {
            return 0;
        }

        /** @var array<int, int> $parent */
        $parent = [];
        foreach ($organizerIds as $id) {
            $parent[(int) $id] = (int) $id;
        }

        foreach ($organizerResponses as $response) {
            $a = (int) $response->congregation_id;
            foreach ($response->normalizedSharedCongregationIds() as $b) {
                if ($a === $b) {
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
        foreach ($organizerIds as $id) {
            $roots[$this->findRoot($parent, (int) $id)] = true;
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
