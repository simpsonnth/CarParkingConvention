<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\TicketChangeRequest;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class TicketChangeRequests extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 25;

    /** pending | completed | all */
    public string $statusFilter = 'pending';

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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        if (! in_array($status, ['pending', 'completed', 'all'], true)) {
            return;
        }

        $this->statusFilter = $status;
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

    public function markCompleted(int $id): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        $request = TicketChangeRequest::query()->findOrFail($id);
        if ($request->isCompleted()) {
            return;
        }

        $request->update([
            'status' => TicketChangeRequest::STATUS_COMPLETED,
            'actioned_at' => now(),
            'actioned_by' => auth()->id(),
        ]);

        if ($this->viewingId === $id) {
            $this->closeDetail();
        }

        try {
            Flux::toast(__('management.ticket_change_requests.marked_completed'));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.marked_completed'));
        }

        $this->resetPage();
    }

    public function markPending(int $id): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        $request = TicketChangeRequest::query()->findOrFail($id);
        if ($request->isPending()) {
            return;
        }

        $request->update([
            'status' => TicketChangeRequest::STATUS_PENDING,
            'actioned_at' => null,
            'actioned_by' => null,
        ]);

        if ($this->viewingId === $id) {
            $this->closeDetail();
        }

        try {
            Flux::toast(__('management.ticket_change_requests.marked_pending'));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.marked_pending'));
        }

        $this->resetPage();
    }

    public function render()
    {
        $query = TicketChangeRequest::query()->with('actionedByUser:id,name');

        if ($this->statusFilter === 'pending') {
            $query->where('status', TicketChangeRequest::STATUS_PENDING);
        } elseif ($this->statusFilter === 'completed') {
            $query->where('status', TicketChangeRequest::STATUS_COMPLETED);
        }

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('congregation', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        $rows = $query->orderByDesc('created_at')->paginate($this->perPage);

        $pendingCount = TicketChangeRequest::query()
            ->where('status', TicketChangeRequest::STATUS_PENDING)
            ->count();
        $completedCount = TicketChangeRequest::query()
            ->where('status', TicketChangeRequest::STATUS_COMPLETED)
            ->count();
        $total = $pendingCount + $completedCount;

        $viewing = $this->viewingId !== null
            ? TicketChangeRequest::query()->with('actionedByUser:id,name')->find($this->viewingId)
            : null;

        return view('livewire.admin.ticket-change-requests', [
            'rows' => $rows,
            'total' => $total,
            'pendingCount' => $pendingCount,
            'completedCount' => $completedCount,
            'viewing' => $viewing,
        ]);
    }
}
