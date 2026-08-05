<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\TicketChangeRequest;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TicketChangeRequestDetail extends Component
{
    public TicketChangeRequest $ticketChangeRequest;

    public string $adminNotes = '';

    public function mount(TicketChangeRequest $ticketChangeRequest): void
    {
        $this->ticketChangeRequest = $ticketChangeRequest->load('actionedByUser:id,name');
        $this->adminNotes = $ticketChangeRequest->admin_notes ?? '';
    }

    public function saveAdminNotes(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        $this->validate([
            'adminNotes' => 'nullable|string|max:5000',
        ]);

        $this->ticketChangeRequest->update([
            'admin_notes' => trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null,
        ]);
        $this->ticketChangeRequest->refresh();

        try {
            Flux::toast(__('management.ticket_change_requests.admin_notes_saved'));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.admin_notes_saved'));
        }
    }

    public function markCompleted(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        if ($this->ticketChangeRequest->isCompleted()) {
            return;
        }

        $this->validate([
            'adminNotes' => 'nullable|string|max:5000',
        ]);

        $this->ticketChangeRequest->update([
            'status' => TicketChangeRequest::STATUS_COMPLETED,
            'actioned_at' => now(),
            'actioned_by' => auth()->id(),
            'admin_notes' => trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null,
        ]);
        $this->ticketChangeRequest->refresh()->load('actionedByUser:id,name');

        try {
            Flux::toast(__('management.ticket_change_requests.marked_completed'));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.marked_completed'));
        }
    }

    public function markPending(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        if ($this->ticketChangeRequest->isPending()) {
            return;
        }

        $this->ticketChangeRequest->update([
            'status' => TicketChangeRequest::STATUS_PENDING,
            'actioned_at' => null,
            'actioned_by' => null,
        ]);
        $this->ticketChangeRequest->refresh()->load('actionedByUser:id,name');

        try {
            Flux::toast(__('management.ticket_change_requests.marked_pending'));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.marked_pending'));
        }
    }

    public function copyPersonEmailSummary(): void
    {
        $group = TicketChangeRequest::query()
            ->forSamePerson($this->ticketChangeRequest->name, $this->ticketChangeRequest->congregation)
            ->orderBy('created_at')
            ->get();

        $lines = [
            __('management.ticket_change_requests.email_summary_name').': '.$this->ticketChangeRequest->name,
            __('management.ticket_change_requests.email_summary_congregation').': '.$this->ticketChangeRequest->congregation,
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

    public function markAllRelatedPendingCompleted()
    {
        return $this->completePendingGroup(
            TicketChangeRequest::query()
                ->forSamePerson($this->ticketChangeRequest->name, $this->ticketChangeRequest->congregation)
                ->where('status', TicketChangeRequest::STATUS_PENDING)
                ->pluck('id'),
            __('management.ticket_change_requests.no_related_pending')
        );
    }

    public function copyCongregationEmailSummary(): void
    {
        $group = TicketChangeRequest::query()
            ->forSameCongregation($this->ticketChangeRequest->congregation)
            ->orderByRaw('LOWER(TRIM(name))')
            ->orderBy('created_at')
            ->get();

        $lines = [
            __('management.ticket_change_requests.email_summary_congregation').': '.$this->ticketChangeRequest->congregation,
            '',
            __('management.ticket_change_requests.email_summary_requests').':',
        ];

        foreach ($group as $index => $request) {
            $status = $request->isPending()
                ? __('management.ticket_change_requests.status_pending')
                : __('management.ticket_change_requests.status_completed');
            $submitted = $request->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '';
            $lines[] = ($index + 1).'. '.$request->name.' ['.$status.'] '.$submitted;
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

    public function markAllCongregationPendingCompleted()
    {
        return $this->completePendingGroup(
            TicketChangeRequest::query()
                ->forSameCongregation($this->ticketChangeRequest->congregation)
                ->where('status', TicketChangeRequest::STATUS_PENDING)
                ->pluck('id'),
            __('management.ticket_change_requests.no_congregation_pending')
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $pendingIds
     */
    private function completePendingGroup($pendingIds, string $emptyMessage)
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        $this->validate([
            'adminNotes' => 'nullable|string|max:5000',
        ]);

        $adminNote = trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null;

        if ($pendingIds->isEmpty()) {
            try {
                Flux::toast($emptyMessage, variant: 'warning');
            } catch (\Throwable) {
                session()->flash('status', $emptyMessage);
            }

            return null;
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

        if ($adminNote !== null) {
            $this->ticketChangeRequest->update(['admin_notes' => $adminNote]);

            TicketChangeRequest::query()
                ->whereIn('id', $pendingIds)
                ->whereKeyNot($this->ticketChangeRequest->id)
                ->where(function ($q): void {
                    $q->whereNull('admin_notes')->orWhere('admin_notes', '');
                })
                ->update(['admin_notes' => $adminNote]);
        }

        $count = $pendingIds->count();

        try {
            Flux::toast(__('management.ticket_change_requests.marked_all_completed', ['count' => $count]));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.marked_all_completed', ['count' => $count]));
        }

        return $this->redirect(
            route('admin.ticket-change-requests'),
            navigate: true
        );
    }

    public function render()
    {
        $this->ticketChangeRequest->refresh()->loadMissing('actionedByUser:id,name');

        $relatedRequests = TicketChangeRequest::query()
            ->forSamePerson(
                $this->ticketChangeRequest->name,
                $this->ticketChangeRequest->congregation,
                [$this->ticketChangeRequest->id]
            )
            ->orderByDesc('created_at')
            ->get();

        $relatedPendingCount = $relatedRequests
            ->where('status', TicketChangeRequest::STATUS_PENDING)
            ->count()
            + ($this->ticketChangeRequest->isPending() ? 1 : 0);

        $congregationRequests = TicketChangeRequest::query()
            ->forSameCongregation(
                $this->ticketChangeRequest->congregation,
                [$this->ticketChangeRequest->id]
            )
            ->orderByRaw('LOWER(TRIM(name))')
            ->orderByDesc('created_at')
            ->get();

        $congregationPeopleCount = collect($congregationRequests)
            ->map(fn (TicketChangeRequest $r): string => mb_strtolower(trim($r->name)))
            ->push(mb_strtolower(trim($this->ticketChangeRequest->name)))
            ->unique()
            ->count();

        $congregationPendingCount = $congregationRequests
            ->where('status', TicketChangeRequest::STATUS_PENDING)
            ->count()
            + ($this->ticketChangeRequest->isPending() ? 1 : 0);

        return view('livewire.admin.ticket-change-request-detail', [
            'relatedRequests' => $relatedRequests,
            'relatedPendingCount' => $relatedPendingCount,
            'congregationRequests' => $congregationRequests,
            'congregationPeopleCount' => $congregationPeopleCount,
            'congregationPendingCount' => $congregationPendingCount,
        ]);
    }
}
