<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\HotelGuestParkingRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class HotelGuestParkingRequests extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 25;

    /** pending | approved | rejected | all */
    public string $statusFilter = 'pending';

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
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            return;
        }

        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function render()
    {
        $base = HotelGuestParkingRequest::query();

        $pendingCount = (clone $base)->where('status', HotelGuestParkingRequest::STATUS_PENDING)->count();
        $approvedCount = (clone $base)->where('status', HotelGuestParkingRequest::STATUS_APPROVED)->count();
        $rejectedCount = (clone $base)->where('status', HotelGuestParkingRequest::STATUS_REJECTED)->count();
        $total = (clone $base)->count();

        $query = HotelGuestParkingRequest::query()
            ->with(['actionedByUser:id,name', 'carPark:id,name']);

        if ($this->statusFilter === 'pending') {
            $query->where('status', HotelGuestParkingRequest::STATUS_PENDING);
        } elseif ($this->statusFilter === 'approved') {
            $query->where('status', HotelGuestParkingRequest::STATUS_APPROVED);
        } elseif ($this->statusFilter === 'rejected') {
            $query->where('status', HotelGuestParkingRequest::STATUS_REJECTED);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('vehicle_registration', 'like', $like)
                    ->orWhere('contact_number', 'like', $like);
            });
        }

        $rows = $query
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.admin.hotel-guest-parking-requests', [
            'rows' => $rows,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'total' => $total,
        ]);
    }
}
