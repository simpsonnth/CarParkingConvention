<?php

namespace App\Livewire\Admin;

use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use App\Services\CoachCoverageReport;
use App\Services\CoachRegistrationMetrics;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Coaches extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStayingOnSite = 'any';

    public string $sortBy = 'congregation';

    public string $sortDir = 'asc';

    public int $perPage = 25;

    public bool $modalOpen = false;

    public ?ParkingRegistration $editingRegistration = null;

    public string $name = '';

    public string $congregation = '';

    public string $carParkId = '';

    public string $vehicleReg = '';

    public string $contactNumber = '';

    public string $email = '';

    public bool $coachCaptainToBeAssigned = false;

    public string $sharingWithOtherCongregations = '0';

    public string $sharingCongregationsNotes = '';

    /** @var list<string> */
    public array $days = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStayingOnSite(): void
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

    /**
     * Survey responses keyed by trimmed lowercase congregation name.
     *
     * @return array<string, CongregationNumbersResponse|null>
     */
    #[Computed]
    public function surveyByCongregationName(): array
    {
        $map = [];
        Congregation::query()
            ->with('numbersResponse')
            ->get()
            ->each(function (Congregation $congregation) use (&$map): void {
                $key = mb_strtolower(trim((string) $congregation->name), 'UTF-8');
                if ($key !== '') {
                    $map[$key] = $congregation->numbersResponse;
                }
            });

        return $map;
    }

    /**
     * Congregation names keyed by id for resolving survey sharing partners.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function congregationNamesById(): array
    {
        $map = [];
        Congregation::query()
            ->get(['id', 'name'])
            ->each(function (Congregation $congregation) use (&$map): void {
                $map[(int) $congregation->id] = (string) $congregation->name;
            });

        return $map;
    }

    public function surveyResponseForRegistration(ParkingRegistration $registration): ?CongregationNumbersResponse
    {
        $key = mb_strtolower(trim((string) $registration->congregation), 'UTF-8');
        if ($key === '') {
            return null;
        }

        return $this->surveyByCongregationName[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public function sharingPartnerNames(?CongregationNumbersResponse $survey): array
    {
        if ($survey === null) {
            return [];
        }

        $namesById = $this->congregationNamesById;
        $names = [];

        foreach ($survey->normalizedSharedCongregationIds() as $id) {
            if (isset($namesById[$id])) {
                $names[] = $namesById[$id];
            }
        }

        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    public function coachSizeLabel(?string $coachSize): string
    {
        if ($coachSize === null || $coachSize === '') {
            return __('coaches.survey_unknown');
        }

        $key = 'congregation_numbers.coach_size_'.$coachSize;

        return __($key) !== $key ? __($key) : $coachSize;
    }

    public function updateStayingOnSite(int $id, string $value): void
    {
        $registration = ParkingRegistration::query()
            ->where('vehicle_type', 'coach')
            ->findOrFail($id);

        $staying = match ($value) {
            '1', 'yes' => true,
            '0', 'no' => false,
            default => null,
        };

        $registration->update(['coach_staying_on_site' => $staying]);

        try {
            Flux::toast(__('coaches.toast_staying_updated'));
        } catch (\Throwable) {
            session()->flash('status', __('coaches.toast_staying_updated'));
        }
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->editingRegistration = null;
        $this->resetFormFields();
        $this->modalOpen = true;
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $this->editingRegistration = ParkingRegistration::query()
            ->where('vehicle_type', 'coach')
            ->findOrFail($id);

        $this->name = (string) $this->editingRegistration->name;
        $this->congregation = (string) ($this->editingRegistration->congregation ?? '');
        $this->carParkId = $this->editingRegistration->car_park_id ? (string) $this->editingRegistration->car_park_id : '';
        $this->vehicleReg = (string) ($this->editingRegistration->vehicle_registration ?? '');
        $this->contactNumber = (string) $this->editingRegistration->contact_number;
        $this->email = (string) ($this->editingRegistration->email ?? '');
        $this->coachCaptainToBeAssigned = (bool) ($this->editingRegistration->coach_captain_to_be_assigned ?? false);
        $this->sharingWithOtherCongregations = $this->editingRegistration->sharing_with_other_congregations ? '1' : '0';
        $this->sharingCongregationsNotes = (string) ($this->editingRegistration->sharing_congregations_notes ?? '');
        $this->days = is_array($this->editingRegistration->days) ? array_values($this->editingRegistration->days) : [];
        $this->modalOpen = true;
    }

    public function toggleDay(string $day): void
    {
        if (in_array($day, $this->days, true)) {
            $this->days = array_values(array_diff($this->days, [$day]));
        } else {
            $this->days[] = $day;
        }
    }

    public function save(): void
    {
        $isCreating = $this->editingRegistration === null;

        $rules = [
            'name' => 'required|string|max:255',
            'congregation' => $isCreating
                ? 'required|string|exists:congregations,name'
                : 'required|string|max:255',
            'carParkId' => 'nullable|exists:car_parks,id',
            'vehicleReg' => 'nullable|string|max:20',
            'contactNumber' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'coachCaptainToBeAssigned' => 'boolean',
            'sharingWithOtherCongregations' => 'required|in:0,1',
            'sharingCongregationsNotes' => $this->sharingWithOtherCongregations === '1'
                ? 'required|string|max:1000'
                : 'nullable|string|max:1000',
            'days' => $isCreating ? 'required|array|min:1' : 'nullable|array',
        ];
        $this->validate($rules);

        $sharingWithOther = $this->sharingWithOtherCongregations === '1';
        $sharingNotes = $sharingWithOther ? trim($this->sharingCongregationsNotes) : null;
        $vehicleReg = trim($this->vehicleReg) !== ''
            ? strtoupper(str_replace(' ', '', trim($this->vehicleReg)))
            : null;
        $carParkId = $this->carParkId !== '' ? (int) $this->carParkId : null;

        $payload = [
            'name' => $this->name,
            'congregation' => trim($this->congregation),
            'car_park_id' => $carParkId,
            'vehicle_type' => 'coach',
            'vehicle_registration' => $vehicleReg,
            'contact_number' => $this->contactNumber,
            'email' => $this->email !== '' ? $this->email : null,
            'coach_captain_to_be_assigned' => $this->coachCaptainToBeAssigned,
            'sharing_with_other_congregations' => $sharingWithOther,
            'sharing_congregations_notes' => $sharingNotes,
            'days' => $this->days,
        ];

        if ($isCreating) {
            ParkingRegistration::query()->create($payload);
            $this->resetPage();
            $toast = __('coaches.toast_created');
        } else {
            $this->editingRegistration->update($payload);
            $toast = __('coaches.toast_saved');
        }

        $this->modalOpen = false;
        $this->editingRegistration = null;
        $this->resetFormFields();

        try {
            Flux::toast($toast);
        } catch (\Throwable) {
            session()->flash('status', $toast);
        }
    }

    public function delete(int $id): void
    {
        $registration = ParkingRegistration::query()
            ->where('vehicle_type', 'coach')
            ->findOrFail($id);

        if ($this->editingRegistration?->id === $registration->id) {
            $this->modalOpen = false;
            $this->editingRegistration = null;
            $this->resetFormFields();
        }

        $registration->update(['cancelled_via' => 'admin']);
        $registration->delete();

        try {
            Flux::toast(__('coaches.toast_deleted'));
        } catch (\Throwable) {
            session()->flash('status', __('coaches.toast_deleted'));
        }
    }

    protected function resetFormFields(): void
    {
        $this->name = '';
        $this->congregation = '';
        $this->carParkId = '';
        $this->vehicleReg = '';
        $this->contactNumber = '';
        $this->email = '';
        $this->coachCaptainToBeAssigned = false;
        $this->sharingWithOtherCongregations = '0';
        $this->sharingCongregationsNotes = '';
        $this->days = [];
    }

    protected function coachesQuery()
    {
        $query = ParkingRegistration::query()
            ->where('vehicle_type', 'coach')
            ->with('carPark')
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($q2) use ($term) {
                    $q2->where('name', 'like', $term)
                        ->orWhere('vehicle_registration', 'like', $term)
                        ->orWhere('congregation', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('contact_number', 'like', $term);
                });
            })
            ->when($this->filterStayingOnSite === 'yes', fn ($q) => $q->where('coach_staying_on_site', true))
            ->when($this->filterStayingOnSite === 'no', fn ($q) => $q->where('coach_staying_on_site', false))
            ->when($this->filterStayingOnSite === 'not_set', fn ($q) => $q->whereNull('coach_staying_on_site'));

        $sortColumn = match ($this->sortBy) {
            'name' => 'name',
            'congregation' => 'congregation',
            'created_at' => 'created_at',
            'coach_staying_on_site' => 'coach_staying_on_site',
            default => 'congregation',
        };

        return $query->orderBy($sortColumn, $this->sortDir === 'desc' ? 'desc' : 'asc');
    }

    public function render(CoachRegistrationMetrics $metrics, CoachCoverageReport $coverage)
    {
        return view('livewire.admin.coaches', [
            'coaches' => $this->coachesQuery()->paginate($this->perPage),
            'coachMetrics' => $metrics->summarize(),
            'coachCoverage' => $coverage->summarize(),
            'congregations' => Congregation::query()->orderBy('name')->pluck('name')->all(),
            'carParks' => CarPark::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
