<?php

namespace App\Livewire\Admin;

use App\Models\ParkingIncidentReport;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ParkingIncidents extends Component
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
        $query = ParkingIncidentReport::query()->with('carPark');

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('reporter_name', 'like', $term)
                    ->orWhere('reporter_email', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        $rows = $query->orderByDesc('created_at')->paginate($this->perPage);
        $total = ParkingIncidentReport::query()->count();

        $viewing = $this->viewingId !== null
            ? ParkingIncidentReport::query()->with('carPark')->find($this->viewingId)
            : null;

        return view('livewire.admin.parking-incidents', [
            'rows' => $rows,
            'total' => $total,
            'viewing' => $viewing,
        ]);
    }
}
