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
            ->with('carPark')
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            __('registrations.export.date'),
            __('registrations.export.name'),
            __('registrations.export.congregation'),
            __('registrations.export.car_park'),
            __('registrations.export.type'),
            __('registrations.export.sharing'),
            __('registrations.export.sharing_notes'),
            __('registrations.export.vehicle_reg'),
            __('registrations.export.contact'),
            __('registrations.export.email'),
            __('registrations.export.elderly_infirm'),
            __('registrations.export.days'),
        ];
    }

    public function map($row): array
    {
        $sharing = ($row->vehicle_type ?? 'car') === 'coach' && ($row->sharing_with_other_congregations ?? false)
            ? __('registrations.yes')
            : (($row->vehicle_type ?? 'car') === 'coach' ? __('registrations.no') : '');
        return [
            $row->created_at?->format('Y-m-d H:i'),
            $row->name,
            $row->congregation,
            $row->carPark?->name ?? '',
            ucfirst($row->vehicle_type ?? 'car'),
            $sharing,
            $row->sharing_congregations_notes ?? '',
            $row->vehicle_registration ?? '',
            $row->contact_number,
            $row->email ?? '',
            ($row->elderly_infirm_parking ?? false) ? __('registrations.yes') : __('registrations.no'),
            is_array($row->days) ? implode(', ', $row->days) : '',
        ];
    }
}
