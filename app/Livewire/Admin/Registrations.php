<?php

namespace App\Livewire\Admin;

use App\Actions\Registrations\SendCarParkTicketsEmail;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Services\CongregationNumbersReportMetrics;
use App\Services\ParkingRegistrationAttendanceByDayMetrics;
use App\Services\ParkingRegistrationDuplicateSignals;
use App\Services\ParkingRegistrationListQuery;
use App\Support\ParkingRegistrationListFilters;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('components.layouts.app')]
class Registrations extends Component
{
    use WithPagination;

    public $search = '';

    public array $selectedIds = [];

    /** @var int Per-page options: 25, 50, 100 */
    public int $perPage = 25;

    /** Sort: column key and direction */
    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    /** Filter panel visibility */
    public bool $filterOpen = false;

    /** Applied filters (used in query) */
    public array $filterCongregations = [];

    public array $filterCarParks = [];

    public array $filterVehicleType = [];

    public array $filterDays = [];

    /** @var bool|null true = yes, false = no, null = any */
    public $filterElderlyInfirm = null;

    /** Draft filter values (in fly-out before Apply) */
    public array $filterDraftCongregations = [];

    public array $filterDraftCarParks = [];

    public array $filterDraftVehicleType = [];

    public array $filterDraftDays = [];

    /** @var string|null 'any' | '1' | '0' for draft panel */
    public $filterDraftElderlyInfirm = 'any';

    /** Narrow congregation checkboxes in the filter drawer */
    public string $filterDraftCongregationSearch = '';

    /** Rows whose email or vehicle reg appears on 2+ registrations (same rules as duplicate badges) */
    public bool $filterDuplicatesOnly = false;

    public bool $filterDraftDuplicatesOnly = false;

    public bool $filterUnassignedCarPark = false;

    public bool $filterDraftUnassignedCarPark = false;

    public bool $modalOpen = false;

    public bool $bulkAssignCarParkModalOpen = false;

    public bool $sendTicketsModalOpen = false;

    public string $ticketEmailTo = '';

    public bool $sendingTickets = false;

    public bool $ticketsSentSuccessOpen = false;

    public string $ticketsSentSuccessMessage = '';

    public ?ParkingRegistration $editingRegistration = null;

    /** For bulk assign congregation to car park */
    public $bulkAssignCarParkId = '';

    /** For bulk assign selected registrations (individuals) to car park */
    public $bulkAssignIndividualCarParkId = '';

    // Form Fields
    public $name = '';

    public $congregation = '';

    public $carParkId = '';

    public $vehicleType = 'car';

    public $vehicleReg = '';

    public $contactNumber = '';

    public $email = '';

    public $elderlyInfirmParking = '0';

    public $sharingWithOtherCongregations = '0';

    public $sharingCongregationsNotes = '';

    public bool $coachCaptainToBeAssigned = false;

    public $days = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function setSort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function openFilterPanel(): void
    {
        $this->filterDraftCongregations = $this->filterCongregations;
        $this->filterDraftCarParks = $this->filterCarParks;
        $this->filterDraftVehicleType = $this->filterVehicleType;
        $this->filterDraftDays = $this->filterDays;
        $this->filterDraftElderlyInfirm = $this->filterElderlyInfirm === null ? 'any' : ($this->filterElderlyInfirm ? '1' : '0');
        $this->filterDraftDuplicatesOnly = $this->filterDuplicatesOnly;
        $this->filterDraftUnassignedCarPark = $this->filterUnassignedCarPark;
        $this->filterOpen = true;
    }

    public function applyFilters(): void
    {
        $this->filterCongregations = $this->filterDraftCongregations;
        $this->filterCarParks = array_map('intval', $this->filterDraftCarParks);
        $this->filterVehicleType = $this->filterDraftVehicleType;
        $this->filterDays = ParkingRegistrationListFilters::normalizeDays($this->filterDraftDays);
        $draft = $this->filterDraftElderlyInfirm;
        $this->filterElderlyInfirm = ($draft === 'any' || $draft === '' || $draft === null) ? null : (bool) (int) $draft;
        $this->filterDuplicatesOnly = $this->filterDraftDuplicatesOnly;
        $this->filterUnassignedCarPark = $this->filterDraftUnassignedCarPark;
        $this->filterOpen = false;
        $this->resetPage();
    }

    public function cancelFilters(): void
    {
        $this->filterOpen = false;
    }

    public function clearFilters(): void
    {
        $this->filterCongregations = [];
        $this->filterCarParks = [];
        $this->filterVehicleType = [];
        $this->filterDays = [];
        $this->filterElderlyInfirm = null;
        $this->filterDraftCongregations = [];
        $this->filterDraftCarParks = [];
        $this->filterDraftVehicleType = [];
        $this->filterDraftDays = [];
        $this->filterDraftElderlyInfirm = 'any';
        $this->filterDraftCongregationSearch = '';
        $this->filterDuplicatesOnly = false;
        $this->filterDraftDuplicatesOnly = false;
        $this->filterUnassignedCarPark = false;
        $this->filterDraftUnassignedCarPark = false;
        $this->resetPage();
    }

    public function getAppliedFiltersCount(): int
    {
        $n = 0;
        if (! empty($this->filterCongregations)) {
            $n += count($this->filterCongregations);
        }
        if (! empty($this->filterCarParks)) {
            $n += count($this->filterCarParks);
        }
        if (! empty($this->filterVehicleType)) {
            $n += count($this->filterVehicleType);
        }
        if (! empty($this->filterDays)) {
            $n += count($this->filterDays);
        }
        if ($this->filterElderlyInfirm !== null) {
            $n += 1;
        }
        if ($this->filterDuplicatesOnly) {
            $n += 1;
        }
        if ($this->filterUnassignedCarPark) {
            $n += 1;
        }

        return $n;
    }

    #[Computed]
    public function exportUrl(): string
    {
        return route('admin.registrations.export', ParkingRegistrationListFilters::fromLivewire($this)->toQueryArray());
    }

    /** @return list<string> */
    #[Computed]
    public function congregationsFilterOptions(): array
    {
        $names = Congregation::query()->orderBy('name')->pluck('name')->all();
        $names = array_values(array_filter(
            $names,
            fn (string $name): bool => ! in_array($name, ParkingRegistrationListQuery::CIRCUIT_OVERSEER_CONGREGATION_LABELS, true)
        ));
        $names[] = 'Circuit Overseer';
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        $term = mb_strtolower(trim($this->filterDraftCongregationSearch));
        if ($term === '') {
            return $names;
        }

        return array_values(array_filter($names, function (string $name) use ($term): bool {
            return str_contains(mb_strtolower($name, 'UTF-8'), $term);
        }));
    }

    public function edit($id)
    {
        $this->editingRegistration = ParkingRegistration::findOrFail($id);

        $this->name = $this->editingRegistration->name;
        $this->congregation = $this->editingRegistration->congregation;
        $this->carParkId = $this->editingRegistration->car_park_id ? (string) $this->editingRegistration->car_park_id : '';
        $this->vehicleType = $this->editingRegistration->vehicle_type ?? 'car';
        $this->vehicleReg = $this->editingRegistration->vehicle_registration;
        $this->contactNumber = $this->editingRegistration->contact_number;
        $this->email = $this->editingRegistration->email ?? '';
        $this->elderlyInfirmParking = $this->editingRegistration->elderly_infirm_parking ? '1' : '0';
        $this->sharingWithOtherCongregations = $this->editingRegistration->sharing_with_other_congregations ? '1' : '0';
        $this->sharingCongregationsNotes = $this->editingRegistration->sharing_congregations_notes ?? '';
        $this->coachCaptainToBeAssigned = (bool) ($this->editingRegistration->coach_captain_to_be_assigned ?? false);
        $this->days = $this->editingRegistration->days ?? [];

        $this->modalOpen = true;
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->editingRegistration = null;
        $this->name = '';
        $this->congregation = '';
        $this->carParkId = '';
        $this->vehicleType = 'car';
        $this->vehicleReg = '';
        $this->contactNumber = '';
        $this->email = '';
        $this->elderlyInfirmParking = '0';
        $this->sharingWithOtherCongregations = '0';
        $this->sharingCongregationsNotes = '';
        $this->coachCaptainToBeAssigned = false;
        $this->days = [];
        $this->modalOpen = true;
    }

    public function delete($id): void
    {
        ParkingRegistration::findOrFail($id)->delete();
        Flux::toast(__('registrations.deleted'));
    }

    public function toggleSelect(int $id): void
    {
        $key = array_search($id, $this->selectedIds);
        if ($key !== false) {
            array_splice($this->selectedIds, $key, 1);
            $this->selectedIds = array_values($this->selectedIds);
        } else {
            $this->selectedIds = array_values(array_merge($this->selectedIds, [$id]));
        }
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->getRegistrationsQuery()->paginate($this->perPage)->pluck('id')->all();
        if (count(array_intersect($this->selectedIds, $ids)) === count($ids)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $ids));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $ids)));
        }
    }

    public function bulkSetElderlyInfirm(string $value): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');

            return;
        }
        $value = $value === '1' ? true : false;
        $count = ParkingRegistration::whereIn('id', $this->selectedIds)->update(['elderly_infirm_parking' => $value]);
        $this->selectedIds = [];
        Flux::toast(__('registrations.bulk_elderly_infirm_updated', ['count' => $count, 'value' => $value ? __('registrations.yes') : __('registrations.no')]));
    }

    public function openBulkAssignCarParkModal(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');

            return;
        }
        $this->bulkAssignCarParkId = '';
        $this->bulkAssignCarParkModalOpen = true;
    }

    public function bulkAssignCongregationToCarPark(): void
    {
        $selectedIds = array_values(array_unique(array_map('intval', $this->selectedIds)));
        if ($selectedIds === []) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            $this->bulkAssignCarParkModalOpen = false;

            return;
        }
        if ($this->bulkAssignCarParkId === '' || $this->bulkAssignCarParkId === null) {
            Flux::toast(__('registrations.select_car_park_first'), variant: 'warning');

            return;
        }

        $this->validate(['bulkAssignCarParkId' => 'required|exists:car_parks,id']);
        $carParkId = (int) $this->bulkAssignCarParkId;
        $registrations = ParkingRegistration::whereIn('id', $selectedIds)->get();

        // Circuit Overseer rows do not have a congregation default, so assign
        // those selected rows directly.
        $coIds = $registrations
            ->where('is_circuit_overseer', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $coUpdated = 0;
        if ($coIds !== []) {
            $coUpdated = ParkingRegistration::whereIn('id', $coIds)->update(['car_park_id' => $carParkId]);
        }

        $congregationNames = $registrations
            ->where('is_circuit_overseer', false)
            ->pluck('congregation')
            ->unique()
            ->filter()
            ->values();
        $updated = 0;
        $notFound = [];
        foreach ($congregationNames as $name) {
            $congregation = Congregation::where('name', $name)->first();
            if ($congregation) {
                $congregation->update(['car_park_id' => $carParkId]);
                $updated++;
            } else {
                $notFound[] = $name;
            }
        }
        $this->selectedIds = [];
        $this->bulkAssignCarParkModalOpen = false;
        $this->bulkAssignCarParkId = '';
        $totalUpdated = $updated + $coUpdated;
        $msg = __('registrations.bulk_congregation_car_park_assigned', ['count' => $totalUpdated]);
        if (count($notFound) > 0) {
            $msg .= ' '.__('registrations.bulk_congregation_not_found', ['names' => implode(', ', array_slice($notFound, 0, 5)).(count($notFound) > 5 ? '…' : '')]);
        }
        Flux::toast($msg, variant: count($notFound) > 0 || $totalUpdated === 0 ? 'warning' : 'success');
    }

    /** Assign selected registrations (individuals) to a car park — e.g. for elderly/infirm. */
    public function bulkAssignSelectedToCarPark(): void
    {
        $selectedIds = array_values(array_unique(array_map('intval', $this->selectedIds)));
        if ($selectedIds === []) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');

            return;
        }
        if ($this->bulkAssignIndividualCarParkId === '' || $this->bulkAssignIndividualCarParkId === null) {
            Flux::toast(__('registrations.select_car_park_first'), variant: 'warning');

            return;
        }

        $this->validate(['bulkAssignIndividualCarParkId' => 'required|exists:car_parks,id']);
        $count = ParkingRegistration::whereIn('id', $selectedIds)->update(['car_park_id' => (int) $this->bulkAssignIndividualCarParkId]);
        if ($count === 0) {
            Flux::toast(__('registrations.bulk_assign_no_changes'), variant: 'warning');

            return;
        }

        $this->selectedIds = [];
        $this->bulkAssignIndividualCarParkId = '';
        Flux::toast(__('registrations.bulk_individual_car_park_assigned', ['count' => $count]));
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');

            return;
        }
        $count = ParkingRegistration::whereIn('id', $this->selectedIds)->delete();
        $this->selectedIds = [];
        Flux::toast(__('registrations.bulk_deleted', ['count' => $count]));
    }

    /** Soft-delete standard car registrations only (not coaches, not elderly/infirm cars, not Circuit Overseers). */
    public function bulkDeleteStandardCarRegistrations(): void
    {
        $count = ParkingRegistration::query()
            ->where('vehicle_type', 'car')
            ->where('elderly_infirm_parking', false)
            ->where('is_circuit_overseer', false)
            ->delete();

        Flux::toast(__('registrations.bulk_deleted_standard_cars', ['count' => $count]));
    }

    /** Soft-delete elderly/infirm car registrations only (not Circuit Overseers). Coaches are unaffected. */
    public function bulkDeleteDisabledRegistrations(): void
    {
        $count = ParkingRegistration::query()
            ->where('elderly_infirm_parking', true)
            ->where('is_circuit_overseer', false)
            ->delete();

        Flux::toast(__('registrations.bulk_deleted_disabled', ['count' => $count]));
    }

    /** Download a ZIP of master pass PDFs for the selected registrations (redirects to download URL). */
    public function downloadMasterPassesZip()
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');

            return;
        }

        $token = \Illuminate\Support\Str::random(32);
        $ids = array_values(array_map('intval', $this->selectedIds));
        cache()->put('master-passes-zip:'.$token, $ids, now()->addMinutes(2));

        try {
            return $this->redirect(route('admin.registrations.download-passes-zip', ['token' => $token]), navigate: false);
        } catch (Throwable $e) {
            cache()->forget('master-passes-zip:'.$token);
            Flux::toast($e->getMessage(), variant: 'danger');

            return null;
        }
    }

    public function openSendTicketsModal(): void
    {
        abort_unless(auth()->user()?->can('registrations.print'), 403);

        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');

            return;
        }

        $this->ticketEmailTo = '';
        $this->resetErrorBag('ticketEmailTo');
        $this->sendTicketsModalOpen = true;
    }

    public function sendCarParkTickets(SendCarParkTicketsEmail $sender): void
    {
        abort_unless(auth()->user()?->can('registrations.print'), 403);

        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            $this->sendTicketsModalOpen = false;

            return;
        }

        $this->validate([
            'ticketEmailTo' => 'required|email|max:255',
        ]);

        $this->sendingTickets = true;

        try {
            $result = $sender->execute(
                array_values(array_map('intval', $this->selectedIds)),
                $this->ticketEmailTo,
            );

            $this->sendTicketsModalOpen = false;
            $this->ticketEmailTo = '';
            $this->selectedIds = [];

            $this->ticketsSentSuccessMessage = __('registrations.tickets_email_sent', [
                'count' => $result['sent'],
                'email' => $result['to'],
            ]);
            $this->ticketsSentSuccessOpen = true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        } finally {
            $this->sendingTickets = false;
        }
    }

    protected function getRegistrationsQuery()
    {
        return app(ParkingRegistrationListQuery::class)->apply(
            ParkingRegistration::query()->with('carPark'),
            ParkingRegistrationListFilters::fromLivewire($this)
        );
    }

    public function save()
    {
        $isCreating = $this->editingRegistration === null;
        $isCircuitOverseer = (bool) ($this->editingRegistration?->is_circuit_overseer ?? false);

        $rules = [
            'name' => 'required|string|max:255',
            'carParkId' => 'nullable|exists:car_parks,id',
            'vehicleType' => 'required|in:car,coach',
            'contactNumber' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'elderlyInfirmParking' => 'in:0,1',
            'days' => $isCreating ? 'required|array|min:1' : 'nullable|array',
        ];
        if ($isCreating) {
            $rules['congregation'] = 'required|string|exists:congregations,name';
        } elseif ($isCircuitOverseer) {
            $rules['congregation'] = 'nullable|string|max:255';
        } else {
            $rules['congregation'] = 'required|string|max:255';
        }
        $rules['vehicleReg'] = $this->vehicleType === 'car' ? 'required|string|min:2|max:20' : 'nullable|string|max:20';
        if ($this->vehicleType === 'coach') {
            $rules['sharingWithOtherCongregations'] = 'required|in:0,1';
            $rules['sharingCongregationsNotes'] = $this->sharingWithOtherCongregations === '1' ? 'required|string|max:1000' : 'nullable|string|max:1000';
            $rules['coachCaptainToBeAssigned'] = 'boolean';
        }
        $this->validate($rules);

        $this->vehicleReg = $this->vehicleType === 'car' && trim($this->vehicleReg ?? '') !== ''
            ? strtoupper(str_replace(' ', '', trim($this->vehicleReg)))
            : null;

        $carParkId = $this->carParkId ? (int) $this->carParkId : null;
        $sharingWithOther = $this->vehicleType === 'coach' && $this->sharingWithOtherCongregations === '1';
        $sharingNotes = $sharingWithOther ? trim($this->sharingCongregationsNotes) : null;
        $congregation = trim((string) $this->congregation);
        if ($isCircuitOverseer && $congregation === '') {
            $congregation = 'Circuit Overseer';
        }

        $payload = [
            'name' => $this->name,
            'congregation' => $congregation,
            'car_park_id' => $carParkId,
            'vehicle_type' => $this->vehicleType,
            'vehicle_registration' => $this->vehicleReg ?? null,
            'contact_number' => $this->contactNumber,
            'email' => $this->email,
            'elderly_infirm_parking' => filter_var($this->elderlyInfirmParking, FILTER_VALIDATE_BOOLEAN),
            'sharing_with_other_congregations' => $this->vehicleType === 'coach' ? filter_var($this->sharingWithOtherCongregations, FILTER_VALIDATE_BOOLEAN) : false,
            'sharing_congregations_notes' => $sharingNotes,
            'coach_captain_to_be_assigned' => $this->vehicleType === 'coach' ? $this->coachCaptainToBeAssigned : false,
            'days' => $this->days,
        ];

        if ($this->editingRegistration) {
            $this->editingRegistration->update($payload);

            Flux::toast(__('registrations.updated'));
        } else {
            ParkingRegistration::query()->create($payload);

            Flux::toast(__('registrations.created'));
            $this->resetPage();
        }

        $this->modalOpen = false;
        $this->reset('editingRegistration', 'name', 'congregation', 'carParkId', 'vehicleType', 'vehicleReg', 'contactNumber', 'email', 'elderlyInfirmParking', 'sharingWithOtherCongregations', 'sharingCongregationsNotes', 'coachCaptainToBeAssigned', 'days');
    }

    public function toggleDay($day)
    {
        if (in_array($day, $this->days)) {
            $this->days = array_values(array_diff($this->days, [$day]));
        } else {
            $this->days[] = $day;
        }
    }

    public function render()
    {
        $registrations = $this->getRegistrationsQuery()->paginate($this->perPage);

        $reportMetrics = app(CongregationNumbersReportMetrics::class)->compute();
        $expectedTotal = (int) $reportMetrics['combined_total_car_park_tickets'];
        $registrationTotal = (int) ParkingRegistration::query()->count();
        $difference = $expectedTotal - $registrationTotal;
        $completionPercent = $expectedTotal > 0
            ? min(100.0, round(100 * $registrationTotal / $expectedTotal, 1))
            : ($registrationTotal > 0 ? 100.0 : 0.0);

        $attendanceByDay = app(ParkingRegistrationAttendanceByDayMetrics::class)->compute();

        $duplicateSignals = app(ParkingRegistrationDuplicateSignals::class);

        $congregationCarParkByName = Congregation::query()
            ->with('carPark')
            ->whereNotNull('car_park_id')
            ->get()
            ->mapWithKeys(fn (Congregation $congregation) => [trim($congregation->name) => $congregation->carPark])
            ->all();

        return view('livewire.admin.registrations', [
            'registrations' => $registrations,
            'congregations' => Congregation::orderBy('name')->pluck('name'),
            'carParks' => \App\Models\CarPark::orderBy('name')->get(),
            'congregationCarParkByName' => $congregationCarParkByName,
            'registrationReportSummary' => [
                'registration_total' => $registrationTotal,
                'expected_total' => $expectedTotal,
                'difference' => $difference,
                'completion_percent' => $completionPercent,
            ],
            'attendanceByDay' => $attendanceByDay,
            'duplicateEmailKeys' => $duplicateSignals->duplicateNormalizedEmailKeys(),
            'duplicateVehicleRegKeys' => $duplicateSignals->duplicateNormalizedVehicleRegKeys(),
        ]);
    }
}
