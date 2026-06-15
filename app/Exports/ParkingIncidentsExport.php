<?php

namespace App\Exports;

use App\Models\ParkingIncidentReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ParkingIncidentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return ParkingIncidentReport::query()
            ->with('carPark')
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Submitted',
            'Type',
            'Occurred',
            'Location',
            'Car park',
            'Description',
            'Actions taken',
            'Injury reported',
            'Severity',
            'Reporter name',
            'Reporter email',
            'Reporter phone',
        ];
    }

    /**
     * @param  ParkingIncidentReport  $row
     */
    public function map($row): array
    {
        return [
            $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
            $row->type,
            $row->occurred_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
            $row->location,
            $row->carPark?->name ?? '',
            $row->description,
            $row->actions_taken ?? '',
            $row->injury_reported ? 'Yes' : 'No',
            $row->severity ?? '',
            $row->reporter_name,
            $row->reporter_email,
            $row->reporter_phone,
        ];
    }
}
