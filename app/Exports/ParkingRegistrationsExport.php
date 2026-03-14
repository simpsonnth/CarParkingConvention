<?php

namespace App\Exports;

use App\Models\ParkingRegistration;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ParkingRegistrationsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return ParkingRegistration::query()
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            __('registrations.export.date'),
            __('registrations.export.name'),
            __('registrations.export.congregation'),
            __('registrations.export.type'),
            __('registrations.export.vehicle_reg'),
            __('registrations.export.contact'),
            __('registrations.export.email'),
            __('registrations.export.elderly_infirm'),
            __('registrations.export.days'),
        ];
    }

    public function map($row): array
    {
        return [
            $row->created_at?->format('Y-m-d H:i'),
            $row->name,
            $row->congregation,
            ucfirst($row->vehicle_type ?? 'car'),
            $row->vehicle_registration ?? '',
            $row->contact_number,
            $row->email ?? '',
            ($row->elderly_infirm_parking ?? false) ? __('registrations.yes') : __('registrations.no'),
            is_array($row->days) ? implode(', ', $row->days) : '',
        ];
    }
}
