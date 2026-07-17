<?php

namespace App\Livewire\Admin;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
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

    public string $contactNumber = '';

    public string $email = '';

    public bool $coachCaptainToBeAssigned = false;

    public string $sharingWithOtherCongregations = '0';

    public string $sharingCongregationsNotes = '';

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

    public function surveyResponseForRegistration(ParkingRegistration $registration): ?CongregationNumbersResponse
    {
        $key = mb_strtolower(trim((string) $registration->congregation), 'UTF-8');
        if ($key === '') {
            return null;
        }

        return $this->surveyByCongregationName[$key] ?? null;
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

    public function edit(int $id): void
    {
        $this->editingRegistration = ParkingRegistration::query()
            ->where('vehicle_type', 'coach')
            ->findOrFail($id);

        $this->name = (string) $this->editingRegistration->name;
        $this->contactNumber = (string) $this->editingRegistration->contact_number;
        $this->email = (string) ($this->editingRegistration->email ?? '');
        $this->coachCaptainToBeAssigned = (bool) ($this->editingRegistration->coach_captain_to_be_assigned ?? false);
        $this->sharingWithOtherCongregations = $this->editingRegistration->sharing_with_other_congregations ? '1' : '0';
        $this->sharingCongregationsNotes = (string) ($this->editingRegistration->sharing_congregations_notes ?? '');
        $this->modalOpen = true;
    }

    public function save(): void
    {
        if ($this->editingRegistration === null) {
            return;
        }

        $rules = [
            'name' => 'required|string|max:255',
            'contactNumber' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'coachCaptainToBeAssigned' => 'boolean',
            'sharingWithOtherCongregations' => 'required|in:0,1',
            'sharingCongregationsNotes' => $this->sharingWithOtherCongregations === '1'
                ? 'required|string|max:1000'
                : 'nullable|string|max:1000',
        ];
        $this->validate($rules);

        $sharingWithOther = $this->sharingWithOtherCongregations === '1';
        $sharingNotes = $sharingWithOther ? trim($this->sharingCongregationsNotes) : null;

        $this->editingRegistration->update([
            'name' => $this->name,
            'contact_number' => $this->contactNumber,
            'email' => $this->email !== '' ? $this->email : null,
            'coach_captain_to_be_assigned' => $this->coachCaptainToBeAssigned,
            'sharing_with_other_congregations' => $sharingWithOther,
            'sharing_congregations_notes' => $sharingNotes,
        ]);

        $this->modalOpen = false;
        $this->reset('editingRegistration', 'name', 'contactNumber', 'email', 'coachCaptainToBeAssigned', 'sharingWithOtherCongregations', 'sharingCongregationsNotes');

        try {
            Flux::toast(__('coaches.toast_saved'));
        } catch (\Throwable) {
            session()->flash('status', __('coaches.toast_saved'));
        }
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

    public function render(CoachRegistrationMetrics $metrics)
    {
        return view('livewire.admin.coaches', [
            'coaches' => $this->coachesQuery()->paginate($this->perPage),
            'coachMetrics' => $metrics->summarize(),
        ]);
    }
}
