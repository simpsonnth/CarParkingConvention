<?php

namespace App\Livewire\Admin;

use App\Services\SurveyVsRegistrationMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SurveyVsRegistrationReport extends Component
{
    public string $search = '';

    public string $sortBy = 'congregation';

    public string $sortDir = 'asc';

    public function setSort(string $column): void
    {
        $allowed = ['congregation', 'survey_tickets', 'registration_count', 'difference', 'progress_percent'];
        if (! in_array($column, $allowed, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = $column === 'congregation' ? 'asc' : 'desc';
        }
    }

    /**
     * @param  list<array{name: string, uuid: string|null, survey_tickets: int, registration_count: int, difference: int, progress_percent: float}>  $rows
     * @return list<array{name: string, uuid: string|null, survey_tickets: int, registration_count: int, difference: int, progress_percent: float}>
     */
    private function filteredRows(array $rows): array
    {
        $term = mb_strtolower(trim($this->search));
        if ($term === '') {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => str_contains(mb_strtolower($row['name']), $term)
        ));
    }

    /**
     * @param  list<array{name: string, uuid: string|null, survey_tickets: int, registration_count: int, difference: int, progress_percent: float}>  $rows
     * @return list<array{name: string, uuid: string|null, survey_tickets: int, registration_count: int, difference: int, progress_percent: float}>
     */
    private function sortRows(array $rows): array
    {
        $col = $this->sortBy;
        $dir = $this->sortDir === 'desc' ? -1 : 1;

        usort($rows, function (array $a, array $b) use ($col, $dir): int {
            $cmp = match ($col) {
                'congregation' => strnatcasecmp($a['name'], $b['name']),
                'survey_tickets' => $a['survey_tickets'] <=> $b['survey_tickets'],
                'registration_count' => $a['registration_count'] <=> $b['registration_count'],
                'difference' => $a['difference'] <=> $b['difference'],
                'progress_percent' => $a['progress_percent'] <=> $b['progress_percent'],
                default => strnatcasecmp($a['name'], $b['name']),
            };

            if ($cmp === 0 && $col !== 'congregation') {
                return strnatcasecmp($a['name'], $b['name']);
            }

            return $dir * $cmp;
        });

        return $rows;
    }

    public function render(SurveyVsRegistrationMetrics $metrics)
    {
        $svr = $metrics->compute();
        $tableRows = $this->filteredRows($svr['rows']);
        $searchActive = trim($this->search) !== '';
        $noMatches = $searchActive && $tableRows === [];

        if (! $noMatches && $tableRows !== []) {
            $tableRows = $this->sortRows($tableRows);
        }

        return view('livewire.admin.survey-vs-registration-report', [
            'svr' => $svr,
            'tableRows' => $tableRows,
            'noMatches' => $noMatches,
            'searchActive' => $searchActive,
        ]);
    }
}
