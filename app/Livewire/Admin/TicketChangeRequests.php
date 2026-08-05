<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\TicketChangeRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class TicketChangeRequests extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 25;

    public bool $detailModalOpen = false;

    public ?int $viewingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function openDetail(int $id): void
    {
        $this->viewingId = $id;
        $this->detailModalOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailModalOpen = false;
        $this->viewingId = null;
    }

    public function updatedDetailModalOpen(bool $value): void
    {
        if (! $value) {
            $this->viewingId = null;
        }
    }

    public function render()
    {
        $query = TicketChangeRequest::query();

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('congregation', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        $rows = $query->orderByDesc('created_at')->paginate($this->perPage);
        $total = TicketChangeRequest::query()->count();

        $viewing = $this->viewingId !== null
            ? TicketChangeRequest::query()->find($this->viewingId)
            : null;

        return view('livewire.admin.ticket-change-requests', [
            'rows' => $rows,
            'total' => $total,
            'viewing' => $viewing,
        ]);
    }
}
