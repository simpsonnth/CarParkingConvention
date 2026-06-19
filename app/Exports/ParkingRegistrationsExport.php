<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Congregation;
use App\Models\ParkingRegistration;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ParkingRegistrationsExport implements FromQuery, WithHeadings, WithMapping
{
    /** @var array<string, \App\Models\CarPark|null> */
    private array $congregationCarParkByName = [];

    public function query()
    {
        $this->congregationCarParkByName = Congregation::query()
            ->with('carPark')
            ->whereNotNull('car_park_id')
            ->get()
            ->mapWithKeys(fn (Congregation $congregation) => [trim($congregation->name) => $congregation->carPark])
            ->all();

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
            __('registrations.export.coach_captain_tba'),
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
        $isCoach = ($row->vehicle_type ?? 'car') === 'coach';
        $sharing = $isCoach && ($row->sharing_with_other_congregations ?? false)
            ? __('registrations.yes')
            : ($isCoach ? __('registrations.no') : '');
        $coachCaptainTba = $isCoach
            ? (($row->coach_captain_to_be_assigned ?? false) ? __('registrations.yes') : __('registrations.no'))
            : '';

        $effectiveCarPark = $row->carPark
            ?? ($this->congregationCarParkByName[trim($row->congregation ?? '')] ?? null);

        return [
            $row->created_at?->format('Y-m-d H:i'),
            $row->name,
            $row->congregation,
            $effectiveCarPark?->name ?? '',
            ucfirst($row->vehicle_type ?? 'car'),
            $coachCaptainTba,
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
