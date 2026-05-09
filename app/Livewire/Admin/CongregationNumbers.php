<?php

namespace App\Livewire\Admin;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class CongregationNumbers extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 25;

    public string $sortBy = 'updated_at';

    public string $sortDir = 'desc';

    public bool $editModalOpen = false;

    public ?int $editingResponseId = null;

    public int $editCarParkTicketsCount = 0;

    /** @var '0'|'1' */
    public string $editOrganizesCoach = '0';

    /** @var '0'|'1' */
    public string $editSharingCoachWithOthers = '0';

    /** @var list<int> */
    public array $editSharedWithCongregationIds = [];

    public string $editShareSearch = '';

    public string $editCoachSize = '';

    /** @var '0'|'1' */
    public string $editDisabledParkingRequired = '0';

    public string $editDisabledParkingCount = '';

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
        $allowed = ['updated_at', 'car_park_tickets_count', 'congregation'];
        if (! in_array($column, $allowed, true)) {
            return;
        }
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = $column === 'congregation' ? 'asc' : 'desc';
        }
        $this->resetPage();
    }

    public function softDeleteResponse(int $id): void
    {
        $row = CongregationNumbersResponse::query()->findOrFail($id);
        $row->delete();

        try {
            Flux::toast(__('congregation_numbers.deleted_toast'));
        } catch (\Throwable) {
            session()->flash('status', __('congregation_numbers.deleted_toast'));
        }
    }

    #[Computed]
    public function editingCongregation(): ?Congregation
    {
        if ($this->editingResponseId === null) {
            return null;
        }

        return CongregationNumbersResponse::query()
            ->with('congregation')
            ->find($this->editingResponseId)
            ?->congregation;
    }

    #[Computed]
    public function editShareSearchReady(): bool
    {
        return mb_strlen(trim($this->editShareSearch)) >= 2;
    }

    /** Congregations already chosen for shared coach (edit modal). */
    #[Computed]
    public function editSelectedSharedCongregations(): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $this->editSharedWithCongregationIds)));
        if ($ids === []) {
            return collect();
        }

        return Congregation::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function editShareSearchMatches(): Collection
    {
        $term = trim($this->editShareSearch);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        $excludeId = $this->editingCongregation?->id;
        $selected = array_values(array_unique(array_map('intval', $this->editSharedWithCongregationIds)));

        $query = Congregation::query()
            ->orderBy('name')
            ->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        if ($selected !== []) {
            $query->whereNotIn('id', $selected);
        }

        return $query->limit(30)->get(['id', 'name']);
    }

    public function openEdit(int $id): void
    {
        $row = CongregationNumbersResponse::query()->with('congregation')->findOrFail($id);

        $this->editingResponseId = $row->id;
        $this->editCarParkTicketsCount = (int) $row->car_park_tickets_count;
        $this->editOrganizesCoach = $row->organizes_coach ? '1' : '0';

        if ($row->organizes_coach && $row->sharing_coach_with_others === true) {
            $this->editSharingCoachWithOthers = '1';
        } elseif ($row->organizes_coach && $row->sharing_coach_with_others === false) {
            $this->editSharingCoachWithOthers = '0';
        } else {
            $this->editSharingCoachWithOthers = '0';
        }

        $this->editSharedWithCongregationIds = $row->normalizedSharedCongregationIds();
        $this->editShareSearch = '';
        $this->editCoachSize = (string) ($row->coach_size ?? '');
        $this->editDisabledParkingRequired = $row->disabled_parking_required ? '1' : '0';
        $this->editDisabledParkingCount = $row->disabled_parking_required
            ? (string) ($row->disabled_parking_count ?? '')
            : '';

        $this->resetErrorBag();
        $this->editModalOpen = true;
    }

    public function closeEditModal(): void
    {
        $this->editModalOpen = false;
    }

    public function updatedEditModalOpen(bool $value): void
    {
        if (! $value) {
            $this->resetEditFormFields();
        }
    }

    private function resetEditFormFields(): void
    {
        $this->editingResponseId = null;
        $this->editCarParkTicketsCount = 0;
        $this->editOrganizesCoach = '0';
        $this->editSharingCoachWithOthers = '0';
        $this->editSharedWithCongregationIds = [];
        $this->editShareSearch = '';
        $this->editCoachSize = '';
        $this->editDisabledParkingRequired = '0';
        $this->editDisabledParkingCount = '';
        $this->resetErrorBag();
    }

    public function addEditSharedCongregation(int $id): void
    {
        $self = $this->editingCongregation;
        if ($self !== null && $id === $self->id) {
            return;
        }

        $ids = array_map('intval', $this->editSharedWithCongregationIds);
        if (! in_array($id, $ids, true)) {
            $this->editSharedWithCongregationIds[] = $id;
        }

        $this->editShareSearch = '';
    }

    public function removeEditSharedCongregation(int $id): void
    {
        $this->editSharedWithCongregationIds = array_values(array_filter(
            array_map('intval', $this->editSharedWithCongregationIds),
            fn (int $x): bool => $x !== $id
        ));
    }

    public function saveEdit(): void
    {
        if ($this->editingResponseId === null) {
            return;
        }

        $response = CongregationNumbersResponse::query()->with('congregation')->findOrFail($this->editingResponseId);
        $congregation = $response->congregation;
        if ($congregation === null) {
            $this->addError('editCarParkTicketsCount', __('congregation_numbers.invalid_congregation_code'));
            $this->closeEditModal();

            return;
        }

        $rules = [
            'editCarParkTicketsCount' => 'required|integer|min:0',
            'editOrganizesCoach' => 'required|in:0,1',
            'editDisabledParkingRequired' => 'required|in:0,1',
        ];

        if ($this->editOrganizesCoach === '1') {
            $rules['editSharingCoachWithOthers'] = 'required|in:0,1';
            if ($this->editSharingCoachWithOthers === '1') {
                $rules['editSharedWithCongregationIds'] = 'required|array|min:1';
                $rules['editSharedWithCongregationIds.*'] = 'integer|distinct|exists:congregations,id';
                $rules['editCoachSize'] = 'required|string|in:minibus,small_coach,large_coach';
            }
        }

        if ($this->editDisabledParkingRequired === '1') {
            $rules['editDisabledParkingCount'] = 'required|integer|min:1';
        }

        $this->validate($rules);

        $organizes = $this->editOrganizesCoach === '1';
        $sharing = $organizes && $this->editSharingCoachWithOthers === '1';
        $disabledReq = $this->editDisabledParkingRequired === '1';

        $sharedIds = $sharing
            ? array_values(array_unique(array_map('intval', $this->editSharedWithCongregationIds)))
            : [];

        if ($sharing && in_array($congregation->id, $sharedIds, true)) {
            $this->addError('editSharedWithCongregationIds', __('congregation_numbers.cannot_share_with_self'));

            return;
        }

        $payload = [
            'car_park_tickets_count' => (int) $this->editCarParkTicketsCount,
            'organizes_coach' => $organizes,
            'sharing_coach_with_others' => $organizes ? ($this->editSharingCoachWithOthers === '1') : null,
            'shared_with_congregation_ids' => $sharing ? $sharedIds : null,
            'coach_size' => $sharing ? $this->editCoachSize : null,
            'disabled_parking_required' => $disabledReq,
            'disabled_parking_count' => $disabledReq ? (int) $this->editDisabledParkingCount : null,
        ];

        $response->fill($payload);
        $response->save();

        $this->closeEditModal();

        try {
            Flux::toast(__('congregation_numbers.admin_saved_toast'));
        } catch (\Throwable) {
            session()->flash('status', __('congregation_numbers.admin_saved_toast'));
        }
    }

    public function render()
    {
        $query = CongregationNumbersResponse::query()
            ->with(['congregation']);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->whereHas('congregation', function ($q) use ($term) {
                $q->where('name', 'like', $term);
            });
        }

        if ($this->sortBy === 'congregation') {
            $query->join('congregations as cn_sort_cong', 'cn_sort_cong.id', '=', 'congregation_numbers_responses.congregation_id')
                ->orderBy('cn_sort_cong.name', $this->sortDir === 'asc' ? 'asc' : 'desc')
                ->select('congregation_numbers_responses.*');
        } else {
            $dir = $this->sortDir === 'asc' ? 'asc' : 'desc';
            $query->orderBy($this->sortBy, $dir);
        }

        $rows = $query->paginate($this->perPage);

        $allSharedIds = $rows->getCollection()
            ->flatMap(fn (CongregationNumbersResponse $r) => $r->normalizedSharedCongregationIds())
            ->unique()
            ->values()
            ->all();

        $sharedCongregationNameById = $allSharedIds === []
            ? []
            : Congregation::query()->whereIn('id', $allSharedIds)->pluck('name', 'id')->all();

        $congregationsTotal = Congregation::query()->count();
        $congregationsSubmitted = Congregation::query()->whereHas('numbersResponse')->count();
        $congregationsMissing = $congregationsTotal - $congregationsSubmitted;

        return view('livewire.admin.congregation-numbers', [
            'rows' => $rows,
            'sharedCongregationNameById' => $sharedCongregationNameById,
            'congregationsTotal' => $congregationsTotal,
            'congregationsSubmitted' => $congregationsSubmitted,
            'congregationsMissing' => $congregationsMissing,
        ]);
    }
}
