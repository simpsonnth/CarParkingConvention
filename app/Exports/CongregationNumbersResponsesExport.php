<?php

namespace App\Exports;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CongregationNumbersResponsesExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var Collection<int, string> */
    protected Collection $sharedCongregationNameById;

    public function __construct()
    {
        $this->sharedCongregationNameById = collect();
    }

    public function collection(): Collection
    {
        $rows = CongregationNumbersResponse::query()
            ->with(['congregation.carPark'])
            ->orderByDesc('updated_at')
            ->get();

        $sharedIds = $rows
            ->flatMap(fn (CongregationNumbersResponse $r) => $r->normalizedSharedCongregationIds())
            ->unique()
            ->values();

        $this->sharedCongregationNameById = $sharedIds->isEmpty()
            ? collect()
            : Congregation::query()->whereIn('id', $sharedIds->all())->pluck('name', 'id');

        return $rows;
    }

    public function headings(): array
    {
        return [
            __('congregation_numbers.export_all.congregation_name'),
            __('congregation_numbers.export_all.congregation_uuid'),
            __('congregation_numbers.export_all.car_park'),
            __('congregation_numbers.export_all.car_park_tickets'),
            __('congregation_numbers.export_all.organizes_coach'),
            __('congregation_numbers.export_all.sharing_coach'),
            __('congregation_numbers.export_all.shared_with_names'),
            __('congregation_numbers.export_all.coach_size'),
            __('congregation_numbers.export_all.disabled_parking'),
            __('congregation_numbers.export_all.disabled_parking_count'),
            __('congregation_numbers.export_all.updated_at'),
            __('congregation_numbers.export_all.locale'),
        ];
    }

    /**
     * @param  CongregationNumbersResponse  $row
     */
    public function map($row): array
    {
        $cong = $row->congregation;

        $sharingDisplay = '';
        if ($row->organizes_coach) {
            if ($row->sharing_coach_with_others === null) {
                $sharingDisplay = '';
            } else {
                $sharingDisplay = $row->sharing_coach_with_others
                    ? __('congregation_numbers.yes')
                    : __('congregation_numbers.no');
            }
        }

        $sharedNames = collect($row->normalizedSharedCongregationIds())
            ->map(fn (int $id) => $this->sharedCongregationNameById->get($id))
            ->filter()
            ->implode(', ');

        return [
            $cong?->name ?? '',
            $cong !== null ? (string) $cong->uuid : '',
            $cong?->carPark?->name ?? '',
            $row->car_park_tickets_count,
            $row->organizes_coach ? __('congregation_numbers.yes') : __('congregation_numbers.no'),
            $sharingDisplay,
            $sharedNames,
            $this->coachSizeLabel($row->coach_size),
            $row->disabled_parking_required ? __('congregation_numbers.yes') : __('congregation_numbers.no'),
            $row->disabled_parking_count ?? '',
            $row->updated_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
            $row->submitted_locale ?? '',
        ];
    }

    protected function coachSizeLabel(?string $key): string
    {
        return match ($key) {
            'minibus' => __('congregation_numbers.coach_size_minibus'),
            'small_coach' => __('congregation_numbers.coach_size_small_coach'),
            'large_coach' => __('congregation_numbers.coach_size_large_coach'),
            default => '',
        };
    }
}
