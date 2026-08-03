<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ParkingRegistration;

final class CoachCoverageReport
{
    /**
     * Compare expected spreadsheet congregations against coach parking registrations.
     *
     * @return array{
     *     expected_total: int,
     *     registered_expected: int,
     *     missing: list<string>,
     *     unexpected: list<string>,
     *     registered_names: list<string>
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

        $missing = [];
        $registeredExpected = 0;

        foreach ($expected as $name) {
            $key = $this->normalize($name);
            if (isset($registeredByKey[$key])) {
                $registeredExpected++;
            } else {
                $missing[] = $name;
            }
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
        ];
    }

    private function normalize(string $name): string
    {
        return mb_strtolower(trim($name), 'UTF-8');
    }
}
