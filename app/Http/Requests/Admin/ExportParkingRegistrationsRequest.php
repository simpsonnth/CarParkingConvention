<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\ConventionDay;
use App\Support\ParkingRegistrationListFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportParkingRegistrationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'congregations' => ['nullable', 'array'],
            'congregations.*' => ['string', 'max:255'],
            'car_parks' => ['nullable', 'array'],
            'car_parks.*' => ['integer', 'exists:car_parks,id'],
            'vehicle_type' => ['nullable', 'array'],
            'vehicle_type.*' => ['string', 'in:car,coach'],
            'days' => ['nullable', 'array'],
            'days.*' => ['string', Rule::in(ConventionDay::singleDayKeys()), 'distinct'],
            'elderly_infirm' => ['nullable', 'in:0,1'],
            'duplicates_only' => ['nullable', 'boolean'],
            'unassigned_car_park' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'in:created_at,name,congregation,vehicle_registration'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function filters(): ParkingRegistrationListFilters
    {
        return ParkingRegistrationListFilters::fromValidatedArray($this->validated());
    }
}
