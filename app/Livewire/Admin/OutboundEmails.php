<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\OutboundEmail;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class OutboundEmails extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 25;

    /** all | pending | sent | failed | delivered | opened | bounced | complained */
    public string $statusFilter = 'all';

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

    public function setStatusFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'pending', 'sent', 'failed', 'delivered', 'opened', 'bounced', 'complained'], true)) {
            return;
        }

        $this->statusFilter = $filter;
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
        $base = OutboundEmail::query();

        $pendingCount = (clone $base)->where('status', OutboundEmail::STATUS_PENDING)->count();
        $sentCount = (clone $base)->where('status', OutboundEmail::STATUS_SENT)->count();
        $failedCount = (clone $base)->where('status', OutboundEmail::STATUS_FAILED)->count();
        $deliveredCount = (clone $base)->whereNotNull('delivered_at')->count();
        $openedCount = (clone $base)->whereNotNull('opened_at')->count();
        $bouncedCount = (clone $base)->where('provider_status', OutboundEmail::PROVIDER_BOUNCED)->count();
        $complainedCount = (clone $base)->where('provider_status', OutboundEmail::PROVIDER_COMPLAINED)->count();
        $total = (clone $base)->count();

        $query = OutboundEmail::query()->withCount('events');

        if ($this->statusFilter === 'pending') {
            $query->where('status', OutboundEmail::STATUS_PENDING);
        } elseif ($this->statusFilter === 'sent') {
            $query->where('status', OutboundEmail::STATUS_SENT);
        } elseif ($this->statusFilter === 'failed') {
            $query->where('status', OutboundEmail::STATUS_FAILED);
        } elseif ($this->statusFilter === 'delivered') {
            $query->whereNotNull('delivered_at');
        } elseif ($this->statusFilter === 'opened') {
            $query->whereNotNull('opened_at');
        } elseif ($this->statusFilter === 'bounced') {
            $query->where('provider_status', OutboundEmail::PROVIDER_BOUNCED);
        } elseif ($this->statusFilter === 'complained') {
            $query->where('provider_status', OutboundEmail::PROVIDER_COMPLAINED);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('to_email', 'like', $like)
                    ->orWhere('type', 'like', $like)
                    ->orWhere('last_error', 'like', $like)
                    ->orWhere('provider_detail', 'like', $like)
                    ->orWhere('provider_email_id', 'like', $like);
            });
        }

        $rows = $query->orderByDesc('id')->paginate($this->perPage);

        $viewing = null;
        if ($this->viewingId !== null) {
            $viewing = OutboundEmail::query()
                ->with(['events' => fn ($q) => $q->orderByDesc('id')])
                ->find($this->viewingId);
        }

        return view('livewire.admin.outbound-emails', [
            'rows' => $rows,
            'total' => $total,
            'pendingCount' => $pendingCount,
            'sentCount' => $sentCount,
            'failedCount' => $failedCount,
            'deliveredCount' => $deliveredCount,
            'openedCount' => $openedCount,
            'bouncedCount' => $bouncedCount,
            'complainedCount' => $complainedCount,
            'viewing' => $viewing,
        ]);
    }
}
