<?php

namespace App\Livewire\Admin;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use Flux\Flux;
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
