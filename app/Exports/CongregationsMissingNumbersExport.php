<?php

namespace App\Exports;

use App\Models\Congregation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CongregationsMissingNumbersExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Congregation::query()
            ->whereDoesntHave('numbersResponse')
            ->with('carPark')
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            __('congregation_numbers.export_missing.name'),
            __('congregation_numbers.export_missing.congregation_uuid'),
            __('congregation_numbers.export_missing.car_park'),
        ];
    }

    /**
     * @param  Congregation  $row
     */
    public function map($row): array
    {
        return [
            $row->name,
            (string) $row->uuid,
            $row->carPark?->name ?? '',
        ];
    }
}
