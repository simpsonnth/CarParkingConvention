<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;

final class SurveyVsRegistrationMetrics
{
    public function __construct(
        private readonly CongregationNumbersReportMetrics $reportMetrics,
    ) {}

    /**
     * @return array{
     *     rows: list<array{
     *         uuid: string|null,
     *         name: string,
     *         survey_tickets: int,
     *         registration_count: int,
     *         difference: int,
     *         progress_percent: float,
     *     }>,
     *     congregation_registration_subtotal: int,
     *     total_survey_tickets_congregations: int,
     *     circuit_overseer: array{registration_count: int, expected_tickets: int},
     *     unmatched_registrations: int,
     * }
     */
    public function compute(): array
    {
        $report = $this->reportMetrics->compute();
        $coExpected = (int) $report['co_total_car_park_tickets'];

        $coRegistrationCount = (int) ParkingRegistration::query()
            ->where('is_circuit_overseer', true)
            ->count();

        $countByTrimmedName = $this->aggregateNonCoRegistrationCountsByTrimmedCongregation();

        $congregations = Congregation::query()
            ->with('numbersResponse')
            ->orderBy('name')
            ->get();

        $officialTrimmedNames = $congregations
            ->mapWithKeys(fn (Congregation $c): array => [trim((string) $c->name) => true])
            ->all();

        $unmatchedRegistrations = 0;
        foreach ($countByTrimmedName as $trimmedKey => $cnt) {
            if ($trimmedKey === '') {
                $unmatchedRegistrations += $cnt;

                continue;
            }
            if (! isset($officialTrimmedNames[$trimmedKey])) {
                $unmatchedRegistrations += $cnt;
            }
        }

        $rows = [];
        $subtotal = 0;
        $totalSurveyTickets = 0;

        foreach ($congregations as $congregation) {
            $name = (string) $congregation->name;
            $trimmedName = trim($name);
            $surveyTickets = $this->surveyAllocationTotal($congregation->numbersResponse);
            $registrationCount = (int) ($countByTrimmedName[$trimmedName] ?? 0);
            $difference = $surveyTickets - $registrationCount;
            $progressPercent = $surveyTickets > 0
                ? min(100.0, round(100 * $registrationCount / $surveyTickets, 1))
                : ($registrationCount > 0 ? 100.0 : 0.0);

            $rows[] = [
                'uuid' => $congregation->uuid,
                'name' => $name,
                'survey_tickets' => $surveyTickets,
                'registration_count' => $registrationCount,
                'difference' => $difference,
                'progress_percent' => $progressPercent,
            ];

            $subtotal += $registrationCount;
            $totalSurveyTickets += $surveyTickets;
        }

        return [
            'rows' => $rows,
            'congregation_registration_subtotal' => $subtotal,
            'total_survey_tickets_congregations' => $totalSurveyTickets,
            'circuit_overseer' => [
                'registration_count' => $coRegistrationCount,
                'expected_tickets' => $coExpected,
            ],
            'unmatched_registrations' => $unmatchedRegistrations,
        ];
    }

    /**
     * @return array<string, int> trimmed congregation label => count (non–circuit-overseer only)
     */
    private function aggregateNonCoRegistrationCountsByTrimmedCongregation(): array
    {
        return ParkingRegistration::query()
            ->where('is_circuit_overseer', false)
            ->pluck('congregation')
            ->map(fn ($label): string => trim((string) $label))
            ->countBy()
            ->all();
    }

    /**
     * Expected vehicles from register-simple for comparison with registration rows:
     * standard car tickets plus disabled spaces when the survey requested them (same additive model as public registration).
     */
    private function surveyAllocationTotal(?CongregationNumbersResponse $resp): int
    {
        if ($resp === null) {
            return 0;
        }

        $carTickets = (int) $resp->car_park_tickets_count;
        if ($resp->disabled_parking_required) {
            return $carTickets + (int) ($resp->disabled_parking_count ?? 0);
        }

        return $carTickets;
    }
}
