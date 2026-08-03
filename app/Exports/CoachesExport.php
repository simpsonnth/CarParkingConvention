<?php

namespace App\Exports;

use App\Models\Congregation;
use App\Models\ParkingRegistration;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CoachesExport implements FromQuery, WithHeadings, WithMapping
{
    /** @var array<string, \App\Models\CongregationNumbersResponse|null> */
    private array $surveyByName = [];

    /** @var array<int, string> */
    private array $congregationNamesById = [];

    public function __construct()
    {
        Congregation::query()
            ->with('numbersResponse')
            ->get()
            ->each(function (Congregation $congregation): void {
                $this->congregationNamesById[(int) $congregation->id] = (string) $congregation->name;
                $key = mb_strtolower(trim((string) $congregation->name), 'UTF-8');
                if ($key !== '') {
                    $this->surveyByName[$key] = $congregation->numbersResponse;
                }
            });
    }

    public function query()
    {
        return ParkingRegistration::query()
            ->where('vehicle_type', 'coach')
            ->with('carPark')
            ->orderBy('congregation');
    }

    public function headings(): array
    {
        return [
            __('coaches.export.date'),
            __('coaches.export.congregation'),
            __('coaches.export.contact_role'),
            __('coaches.export.name'),
            __('coaches.export.contact'),
            __('coaches.export.email'),
            __('coaches.export.captain_tba'),
            __('coaches.export.staying_on_site'),
            __('coaches.export.survey_coach_size'),
            __('coaches.export.car_park'),
            __('coaches.export.vehicle_reg'),
            __('coaches.export.sharing'),
            __('coaches.export.sharing_notes'),
            __('coaches.export.survey_sharing_partners'),
            __('coaches.export.days'),
        ];
    }

    /**
     * @param  ParkingRegistration  $row
     */
    public function map($row): array
    {
        $key = mb_strtolower(trim((string) $row->congregation), 'UTF-8');
        $survey = $this->surveyByName[$key] ?? null;
        $coachSize = $survey?->coach_size;
        $coachSizeLabel = $coachSize
            ? (__('congregation_numbers.coach_size_'.$coachSize) !== 'congregation_numbers.coach_size_'.$coachSize
                ? __('congregation_numbers.coach_size_'.$coachSize)
                : $coachSize)
            : '';

        $contactRole = ($row->coach_captain_to_be_assigned ?? false)
            ? __('coaches.contact_role_secretary')
            : __('coaches.contact_role_captain');

        $staying = match ($row->coach_staying_on_site) {
            true => __('coaches.staying_yes'),
            false => __('coaches.staying_no'),
            default => __('coaches.staying_not_set'),
        };

        $partnerNames = [];
        if ($survey !== null) {
            foreach ($survey->normalizedSharedCongregationIds() as $id) {
                if (isset($this->congregationNamesById[$id])) {
                    $partnerNames[] = $this->congregationNamesById[$id];
                }
            }
            sort($partnerNames, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return [
            $row->created_at?->format('Y-m-d H:i'),
            $row->congregation,
            $contactRole,
            $row->name,
            $row->contact_number,
            $row->email ?? '',
            ($row->coach_captain_to_be_assigned ?? false) ? __('registrations.yes') : __('registrations.no'),
            $staying,
            $coachSizeLabel,
            $row->carPark?->name ?? '',
            $row->vehicle_registration ?? '',
            ($row->sharing_with_other_congregations ?? false) ? __('registrations.yes') : __('registrations.no'),
            $row->sharing_congregations_notes ?? '',
            implode(', ', $partnerNames),
            is_array($row->days) ? implode(', ', $row->days) : '',
        ];
    }
}
