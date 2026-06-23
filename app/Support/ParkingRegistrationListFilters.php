<?php

declare(strict_types=1);

namespace App\Support;

use App\Livewire\Admin\Registrations;

final readonly class ParkingRegistrationListFilters
{
    /**
     * @param  list<string>  $congregations
     * @param  list<int>  $carParkIds
     * @param  list<string>  $vehicleTypes
     */
    public function __construct(
        public string $search = '',
        public array $congregations = [],
        public array $carParkIds = [],
        public array $vehicleTypes = [],
        public ?bool $elderlyInfirm = null,
        public bool $duplicatesOnly = false,
        public bool $unassignedCarPark = false,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
    ) {}

    public static function fromLivewire(Registrations $component): self
    {
        return new self(
            search: (string) $component->search,
            congregations: $component->filterCongregations,
            carParkIds: array_map('intval', $component->filterCarParks),
            vehicleTypes: $component->filterVehicleType,
            elderlyInfirm: $component->filterElderlyInfirm,
            duplicatesOnly: $component->filterDuplicatesOnly,
            unassignedCarPark: $component->filterUnassignedCarPark,
            sortBy: $component->sortBy,
            sortDir: $component->sortDir,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromValidatedArray(array $data): self
    {
        $elderlyInfirm = null;
        if (array_key_exists('elderly_infirm', $data) && $data['elderly_infirm'] !== null && $data['elderly_infirm'] !== '') {
            $elderlyInfirm = (bool) (int) $data['elderly_infirm'];
        }

        return new self(
            search: (string) ($data['search'] ?? ''),
            congregations: array_values(array_map('strval', (array) ($data['congregations'] ?? []))),
            carParkIds: array_map('intval', (array) ($data['car_parks'] ?? [])),
            vehicleTypes: array_values(array_map('strval', (array) ($data['vehicle_type'] ?? []))),
            elderlyInfirm: $elderlyInfirm,
            duplicatesOnly: filter_var($data['duplicates_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            unassignedCarPark: filter_var($data['unassigned_car_park'] ?? false, FILTER_VALIDATE_BOOLEAN),
            sortBy: (string) ($data['sort_by'] ?? 'created_at'),
            sortDir: (string) ($data['sort_dir'] ?? 'desc'),
        );
    }

    public function hasActiveConstraints(): bool
    {
        return trim($this->search) !== ''
            || $this->congregations !== []
            || $this->carParkIds !== []
            || $this->vehicleTypes !== []
            || $this->elderlyInfirm !== null
            || $this->duplicatesOnly
            || $this->unassignedCarPark;
    }

    /**
     * @return array<string, mixed>
     */
    public function toQueryArray(): array
    {
        $params = [];

        if (trim($this->search) !== '') {
            $params['search'] = trim($this->search);
        }
        if ($this->congregations !== []) {
            $params['congregations'] = $this->congregations;
        }
        if ($this->carParkIds !== []) {
            $params['car_parks'] = $this->carParkIds;
        }
        if ($this->vehicleTypes !== []) {
            $params['vehicle_type'] = $this->vehicleTypes;
        }
        if ($this->elderlyInfirm !== null) {
            $params['elderly_infirm'] = $this->elderlyInfirm ? '1' : '0';
        }
        if ($this->duplicatesOnly) {
            $params['duplicates_only'] = '1';
        }
        if ($this->unassignedCarPark) {
            $params['unassigned_car_park'] = '1';
        }
        if ($this->sortBy !== 'created_at') {
            $params['sort_by'] = $this->sortBy;
        }
        if ($this->sortDir !== 'desc') {
            $params['sort_dir'] = $this->sortDir;
        }

        return $params;
    }
}
