<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\TicketChangeRequests\ApproveTicketChangeRequest;
use App\Models\CarPark;
use App\Models\TicketChangeRequest;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TicketChangeRequestDetail extends Component
{
    public TicketChangeRequest $ticketChangeRequest;

    public string $adminNotes = '';

    public bool $approveModalOpen = false;

    public string $approveCarParkId = '';

    public function mount(TicketChangeRequest $ticketChangeRequest): void
    {
        $this->ticketChangeRequest = $ticketChangeRequest->load([
            'actionedByUser:id,name',
            'parkingRegistration',
        ]);
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

    public function openApproveModal(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        if (! $this->ticketChangeRequest->isPending() || ! $this->ticketChangeRequest->requiresApproval()) {
            return;
        }

        if (! $this->canApprove()) {
            try {
                Flux::toast(__('management.ticket_change_requests.cannot_approve_missing_registration'), variant: 'warning');
            } catch (\Throwable) {
                session()->flash('status', __('management.ticket_change_requests.cannot_approve_missing_registration'));
            }

            return;
        }

        $this->resetErrorBag();
        $this->approveCarParkId = '';
        $this->approveModalOpen = true;
    }

    public function closeApproveModal(): void
    {
        $this->approveModalOpen = false;
        $this->approveCarParkId = '';
    }

    /**
     * Close a pending request without applying its action (e.g. superseded by a cancellation).
     */
    public function closeWithoutApplying(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        if ($this->ticketChangeRequest->isCompleted()) {
            return;
        }

        $this->validate([
            'adminNotes' => 'nullable|string|max:5000',
        ]);

        $note = trim($this->adminNotes);
        if ($note === '') {
            $note = __('management.ticket_change_requests.closed_without_applying_default_note');
        }

        $this->ticketChangeRequest->update([
            'status' => TicketChangeRequest::STATUS_COMPLETED,
            'actioned_at' => now(),
            'actioned_by' => auth()->id(),
            'admin_notes' => $note,
        ]);
        $this->ticketChangeRequest->refresh()->load(['actionedByUser:id,name', 'parkingRegistration']);
        $this->adminNotes = $this->ticketChangeRequest->admin_notes ?? '';

        try {
            Flux::toast(__('management.ticket_change_requests.closed_without_applying'));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.closed_without_applying'));
        }
    }

    public function approve(ApproveTicketChangeRequest $approve): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        if (! $this->canApprove()) {
            $this->addError('approve', __('management.ticket_change_requests.cannot_approve_missing_registration'));

            return;
        }

        $needsCarPark = in_array($this->ticketChangeRequest->request_type, [
            TicketChangeRequest::TYPE_CAR_PARK_CHANGE,
            TicketChangeRequest::TYPE_ADDITION,
        ], true);

        if ($needsCarPark) {
            $this->validate([
                'approveCarParkId' => 'required|exists:car_parks,id',
                'adminNotes' => 'nullable|string|max:5000',
            ], [
                'approveCarParkId.required' => __('management.ticket_change_requests.car_park_required'),
            ]);
        } else {
            $this->validate([
                'adminNotes' => 'nullable|string|max:5000',
            ]);
        }

        try {
            $this->ticketChangeRequest = $approve->execute(
                $this->ticketChangeRequest,
                $user,
                $needsCarPark ? (int) $this->approveCarParkId : null,
                trim($this->adminNotes) !== '' ? trim($this->adminNotes) : null,
            )->load(['actionedByUser:id,name', 'parkingRegistration']);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $mapped = $field === 'approveCarParkId' ? 'approveCarParkId' : (string) $field;
                    $this->addError($mapped, $message);
                }
            }

            return;
        }

        $this->adminNotes = $this->ticketChangeRequest->admin_notes ?? '';
        $this->closeApproveModal();

        try {
            Flux::toast(__('management.ticket_change_requests.approved'));
        } catch (\Throwable) {
            session()->flash('status', __('management.ticket_change_requests.approved'));
        }
    }

    public function markCompleted(): void
    {
        abort_unless(auth()->user()?->can('ticket-change-requests.manage'), 403);

        if ($this->ticketChangeRequest->isCompleted()) {
            return;
        }

        // Approval-required types must use Approve (apply) or Close without applying.
        if ($this->ticketChangeRequest->requiresApproval()) {
            if ($this->canApprove()) {
                $this->openApproveModal();
            } else {
                $this->closeWithoutApplying();
            }

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
            if ($request->isStructured()) {
                $lines[] = __('management.ticket_change_requests.type_'.$request->request_type);
            }
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
     * @return \Illuminate\Support\Collection<int, CarPark>
     */
    #[Computed]
    public function carParks()
    {
        return CarPark::query()->orderBy('name')->get(['id', 'name']);
    }

    public function canApprove(): bool
    {
        if (! $this->ticketChangeRequest->requiresApproval()) {
            return true;
        }

        // Additions create a new registration — no existing ticket required.
        if ($this->ticketChangeRequest->request_type === TicketChangeRequest::TYPE_ADDITION) {
            return true;
        }

        $registration = $this->ticketChangeRequest->parkingRegistration;

        return $registration !== null && ! $registration->trashed();
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
        $this->ticketChangeRequest->refresh()->loadMissing([
            'actionedByUser:id,name',
            'parkingRegistration',
        ]);

        $sameTicketRequests = collect();
        if ($this->ticketChangeRequest->parking_registration_id) {
            $sameTicketRequests = TicketChangeRequest::query()
                ->where('parking_registration_id', $this->ticketChangeRequest->parking_registration_id)
                ->whereKeyNot($this->ticketChangeRequest->id)
                ->orderByDesc('created_at')
                ->get();
        }

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
            'sameTicketRequests' => $sameTicketRequests,
            'canApprove' => $this->canApprove(),
            'registrationCancelled' => $this->ticketChangeRequest->parkingRegistration?->trashed() === true,
        ]);
    }
}
