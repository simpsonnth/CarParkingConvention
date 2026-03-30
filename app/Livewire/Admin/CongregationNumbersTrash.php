<?php

namespace App\Livewire\Admin;

use App\Models\CongregationNumbersResponse;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class CongregationNumbersTrash extends Component
{
    use WithPagination;

    /** @var list<int> */
    public array $selectedIds = [];

    public function restore(int $id): void
    {
        CongregationNumbersResponse::onlyTrashed()->findOrFail($id)->restore();
        $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

        try {
            Flux::toast(__('congregation_numbers.trash_restored'));
        } catch (\Throwable) {
            session()->flash('status', __('congregation_numbers.trash_restored'));
        }
    }

    public function restoreSelected(): void
    {
        if ($this->selectedIds === []) {
            try {
                Flux::toast(__('congregation_numbers.trash_select_first'), variant: 'warning');
            } catch (\Throwable) {
                session()->flash('status', __('congregation_numbers.trash_select_first'));
            }

            return;
        }

        CongregationNumbersResponse::onlyTrashed()->whereIn('id', $this->selectedIds)->restore();
        $count = count($this->selectedIds);
        $this->selectedIds = [];

        try {
            Flux::toast(__('congregation_numbers.trash_bulk_restored', ['count' => $count]));
        } catch (\Throwable) {
            session()->flash('status', __('congregation_numbers.trash_bulk_restored', ['count' => $count]));
        }
    }

    public function forceDelete(int $id): void
    {
        CongregationNumbersResponse::onlyTrashed()->findOrFail($id)->forceDelete();
        $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

        try {
            Flux::toast(__('congregation_numbers.trash_permanently_deleted'));
        } catch (\Throwable) {
            session()->flash('status', __('congregation_numbers.trash_permanently_deleted'));
        }
    }

    public function forceDeleteSelected(): void
    {
        if ($this->selectedIds === []) {
            try {
                Flux::toast(__('congregation_numbers.trash_select_first'), variant: 'warning');
            } catch (\Throwable) {
                session()->flash('status', __('congregation_numbers.trash_select_first'));
            }

            return;
        }

        CongregationNumbersResponse::onlyTrashed()->whereIn('id', $this->selectedIds)->forceDelete();
        $count = count($this->selectedIds);
        $this->selectedIds = [];

        try {
            Flux::toast(__('congregation_numbers.trash_bulk_permanently_deleted', ['count' => $count]));
        } catch (\Throwable) {
            session()->flash('status', __('congregation_numbers.trash_bulk_permanently_deleted', ['count' => $count]));
        }
    }

    public function toggleSelectAll(): void
    {
        $page = $this->rows;
        $ids = $page->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        if (count(array_intersect($this->selectedIds, $ids)) === count($ids)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $ids));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $ids)));
        }
    }

    public function getRowsProperty()
    {
        return CongregationNumbersResponse::onlyTrashed()
            ->with('congregation')
            ->latest('deleted_at')
            ->paginate(25);
    }

    public function render()
    {
        return view('livewire.admin.congregation-numbers-trash', [
            'rows' => $this->rows,
        ]);
    }
}
