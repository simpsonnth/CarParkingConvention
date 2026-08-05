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

    public bool $detailModalOpen = false;

    public bool $createModalOpen = false;

    public ?int $viewingId = null;

    public string $adminNotes = '';

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

    public function openDetail(int $id): void
    {
        $request = TicketChangeRequest::query()->findOrFail($id);
        $this->viewingId = $request->id;
        $this->adminNotes = $request->admin_notes ?? '';
        $this->resetErrorBag();
        $this->detailModalOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailModalOpen = false;
        $this->viewingId = null;
        $this->adminNotes = '';
    }

    public function updatedDetailModalOpen(bool $value): void
    {
        if (! $value) {
            $this->viewingId = null;
            $this->adminNotes = '';
        }
    }

    public function saveAdminNotes(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        if ($this->viewingId === null) {
            return;
        }

        $this->validate([
            'adminNotes' => 'nullable|string|max:5000',
        ]);

        $request = TicketChangeRequest::query()->findOrFail($this->viewingId);
        $request->update([
            'admin_notes' => trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null,
        ]);

        try {
            Flux::toast(__('management.ticket_change_requests.admin_notes_saved'));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.admin_notes_saved'));
        }
    }

    public function markCompleted(int $id): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        $request = TicketChangeRequest::query()->findOrFail($id);
        if ($request->isCompleted()) {
            return;
        }

        $payload = [
            'status' => TicketChangeRequest::STATUS_COMPLETED,
            'actioned_at' => now(),
            'actioned_by' => auth()->id(),
        ];

        // Persist optional admin note from the detail modal when completing from there.
        if ($this->viewingId === $id) {
            $this->validate([
                'adminNotes' => 'nullable|string|max:5000',
            ]);
            $payload['admin_notes'] = trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null;
        }

        $request->update($payload);

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

    public function copyPersonEmailSummary(): void
    {
        if ($this->viewingId === null) {
            return;
        }

        $viewing = TicketChangeRequest::query()->findOrFail($this->viewingId);
        $group = TicketChangeRequest::query()
            ->forSamePerson($viewing->name, $viewing->congregation)
            ->orderBy('created_at')
            ->get();

        $lines = [
            __('management.ticket_change_requests.email_summary_name').': '.$viewing->name,
            __('management.ticket_change_requests.email_summary_congregation').': '.$viewing->congregation,
            '',
            __('management.ticket_change_requests.email_summary_requests').':',
        ];

        foreach ($group as $index => $request) {
            $status = $request->isPending()
                ? __('management.ticket_change_requests.status_pending')
                : __('management.ticket_change_requests.status_completed');
            $submitted = $request->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '';
            $lines[] = ($index + 1).'. ['.$status.'] '.$submitted;
            $lines[] = $request->notes;
            if (filled($request->admin_notes)) {
                $lines[] = __('management.ticket_change_requests.admin_notes').': '.$request->admin_notes;
            }
            $lines[] = '';
        }

        $text = trim(implode("\n", $lines));
        $encoded = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->js('navigator.clipboard.writeText('.$encoded.')');

        try {
            Flux::toast(__('management.ticket_change_requests.email_summary_copied', [
                'count' => $group->count(),
            ]));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.email_summary_copied', [
                'count' => $group->count(),
            ]));
        }
    }

    public function markAllRelatedPendingCompleted(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        if ($this->viewingId === null) {
            return;
        }

        $this->validate([
            'adminNotes' => 'nullable|string|max:5000',
        ]);

        $viewing = TicketChangeRequest::query()->findOrFail($this->viewingId);
        $adminNote = trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null;

        $pendingIds = TicketChangeRequest::query()
            ->forSamePerson($viewing->name, $viewing->congregation)
            ->where('status', TicketChangeRequest::STATUS_PENDING)
            ->pluck('id');

        if ($pendingIds->isEmpty()) {
            try {
                Flux::toast(__('management.ticket_change_requests.no_related_pending'), variant: 'warning');
            } catch (\Throwable) {
                session()->flash('status', __('management.ticket_change_requests.no_related_pending'));
            }

            return;
        }

        $now = now();
        $userId = auth()->id();

        TicketChangeRequest::query()
            ->whereIn('id', $pendingIds)
            ->update([
                'status' => TicketChangeRequest::STATUS_COMPLETED,
                'actioned_at' => $now,
                'actioned_by' => $userId,
            ]);

        // Apply the staff note from the open modal onto the current request only
        // (related rows keep their own notes unless empty — then inherit for context).
        if ($adminNote !== null) {
            $viewing->update(['admin_notes' => $adminNote]);

            TicketChangeRequest::query()
                ->whereIn('id', $pendingIds)
                ->whereKeyNot($viewing->id)
                ->where(function ($q): void {
                    $q->whereNull('admin_notes')->orWhere('admin_notes', '');
                })
                ->update(['admin_notes' => $adminNote]);
        }

        $count = $pendingIds->count();
        $this->closeDetail();
        $this->resetPage();

        try {
            Flux::toast(__('management.ticket_change_requests.marked_all_completed', ['count' => $count]));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.marked_all_completed', ['count' => $count]));
        }
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

        $relatedRequests = collect();
        $relatedPendingCount = 0;
        if ($viewing !== null) {
            $relatedRequests = TicketChangeRequest::query()
                ->forSamePerson($viewing->name, $viewing->congregation, [$viewing->id])
                ->orderByDesc('created_at')
                ->get();
            $relatedPendingCount = $relatedRequests
                ->where('status', TicketChangeRequest::STATUS_PENDING)
                ->count()
                + ($viewing->isPending() ? 1 : 0);
        }

        // Pending count badge per person for rows on the current page.
        $personPendingCounts = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim($row->name)).'|'.mb_strtolower(trim($row->congregation));
            if (! array_key_exists($key, $personPendingCounts)) {
                $personPendingCounts[$key] = TicketChangeRequest::query()
                    ->forSamePerson($row->name, $row->congregation)
                    ->where('status', TicketChangeRequest::STATUS_PENDING)
                    ->count();
            }
        }

        return view('livewire.admin.ticket-change-requests', [
            'rows' => $rows,
            'total' => $total,
            'pendingCount' => $pendingCount,
            'completedCount' => $completedCount,
            'viewing' => $viewing,
            'relatedRequests' => $relatedRequests,
            'relatedPendingCount' => $relatedPendingCount,
            'personPendingCounts' => $personPendingCounts,
        ]);
    }
}
