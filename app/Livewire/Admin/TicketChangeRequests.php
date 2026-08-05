<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Congregation;
use App\Models\TicketChangeRequest;
use Flux\Flux;
use Livewire\Attributes\Computed;
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

    public bool $createModalOpen = false;

    public string $createName = '';

    public string $createCongregation = '';

    public string $createNotes = '';

    public string $createAdminNotes = '';

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

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        $this->resetErrorBag();
        $this->createName = '';
        $this->createCongregation = '';
        $this->createNotes = '';
        $this->createAdminNotes = '';
        $this->createModalOpen = true;
    }

    public function closeCreate(): void
    {
        $this->createModalOpen = false;
        $this->createName = '';
        $this->createCongregation = '';
        $this->createNotes = '';
        $this->createAdminNotes = '';
    }

    public function updatedCreateModalOpen(bool $value): void
    {
        if (! $value) {
            $this->createName = '';
            $this->createCongregation = '';
            $this->createNotes = '';
            $this->createAdminNotes = '';
        }
    }

    public function saveCreate(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        $this->validate([
            'createName' => 'required|string|max:255',
            'createCongregation' => 'required|string|exists:congregations,name',
            'createNotes' => 'required|string|min:10|max:5000',
            'createAdminNotes' => 'nullable|string|max:5000',
        ]);

        TicketChangeRequest::query()->create([
            'name' => trim($this->createName),
            'congregation' => trim($this->createCongregation),
            'notes' => trim($this->createNotes),
            'admin_notes' => trim($this->createAdminNotes) !== '' ? trim($this->createAdminNotes) : null,
            'status' => TicketChangeRequest::STATUS_PENDING,
        ]);

        $this->closeCreate();
        $this->statusFilter = 'pending';
        $this->resetPage();

        try {
            Flux::toast(__('management.ticket_change_requests.created'));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.created'));
        }
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function congregations(): array
    {
        return Congregation::query()->orderBy('name')->pluck('name')->all();
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
                    ->orWhere('notes', 'like', $term)
                    ->orWhere('admin_notes', 'like', $term);
            });
        }

        $rows = $query
            ->orderByRaw('LOWER(TRIM(congregation))')
            ->orderByRaw('LOWER(TRIM(name))')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $pendingCount = TicketChangeRequest::query()
            ->where('status', TicketChangeRequest::STATUS_PENDING)
            ->count();
        $completedCount = TicketChangeRequest::query()
            ->where('status', TicketChangeRequest::STATUS_COMPLETED)
            ->count();
        $total = $pendingCount + $completedCount;

        $personPendingCounts = [];
        $congregationStats = [];
        foreach ($rows as $row) {
            $personKey = mb_strtolower(trim($row->name)).'|'.mb_strtolower(trim($row->congregation));
            if (! array_key_exists($personKey, $personPendingCounts)) {
                $personPendingCounts[$personKey] = TicketChangeRequest::query()
                    ->forSamePerson($row->name, $row->congregation)
                    ->where('status', TicketChangeRequest::STATUS_PENDING)
                    ->count();
            }

            $congKey = mb_strtolower(trim($row->congregation));
            if (! array_key_exists($congKey, $congregationStats)) {
                $congRows = TicketChangeRequest::query()
                    ->forSameCongregation($row->congregation)
                    ->get(['name', 'status']);

                $people = $congRows
                    ->map(fn (TicketChangeRequest $r): string => mb_strtolower(trim($r->name)))
                    ->unique()
                    ->count();
                $pendingPeople = $congRows
                    ->where('status', TicketChangeRequest::STATUS_PENDING)
                    ->map(fn (TicketChangeRequest $r): string => mb_strtolower(trim($r->name)))
                    ->unique()
                    ->count();
                $congPending = $congRows
                    ->where('status', TicketChangeRequest::STATUS_PENDING)
                    ->count();

                $congregationStats[$congKey] = [
                    'label' => $row->congregation,
                    'people' => $people,
                    'pending_people' => $pendingPeople,
                    'pending' => $congPending,
                    'total' => $congRows->count(),
                ];
            }
        }

        return view('livewire.admin.ticket-change-requests', [
            'rows' => $rows,
            'total' => $total,
            'pendingCount' => $pendingCount,
            'completedCount' => $completedCount,
            'personPendingCounts' => $personPendingCounts,
            'congregationStats' => $congregationStats,
        ]);
    }
}
