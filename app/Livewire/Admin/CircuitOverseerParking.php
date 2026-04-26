<?php

namespace App\Livewire\Admin;

use App\Models\CircuitOverseerParkingRequirement;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class CircuitOverseerParking extends Component
{
    use WithPagination;

    public string $search = '';

    public string $firstName = '';

    public int $carParkTicketsCount = 0;

    public bool $disabledParkingRequired = false;

    public ?int $rowId = null;

    public bool $modalOpen = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset('firstName', 'carParkTicketsCount', 'disabledParkingRequired', 'rowId');
        $this->carParkTicketsCount = 0;
        $this->disabledParkingRequired = false;
        $this->modalOpen = true;
    }

    public function edit(int $id): void
    {
        $row = CircuitOverseerParkingRequirement::query()->findOrFail($id);
        $this->rowId = $row->id;
        $this->firstName = $row->first_name;
        $this->carParkTicketsCount = $row->car_park_tickets_count;
        $this->disabledParkingRequired = $row->disabled_parking_required;
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'firstName' => 'required|string|max:255',
            'carParkTicketsCount' => 'required|integer|min:0',
            'disabledParkingRequired' => 'boolean',
        ]);

        $payload = [
            'first_name' => $this->firstName,
            'car_park_tickets_count' => $this->carParkTicketsCount,
            'disabled_parking_required' => $this->disabledParkingRequired,
        ];

        if ($this->rowId !== null) {
            $row = CircuitOverseerParkingRequirement::query()->findOrFail($this->rowId);
            $row->update($payload);
        } else {
            CircuitOverseerParkingRequirement::query()->create($payload);
        }

        $this->modalOpen = false;
        $this->reset('firstName', 'carParkTicketsCount', 'disabledParkingRequired', 'rowId');
        $this->carParkTicketsCount = 0;
        $this->disabledParkingRequired = false;

        try {
            Flux::toast(__('reports.co_toast_saved'));
        } catch (\Throwable) {
            session()->flash('status', __('reports.co_toast_saved'));
        }
    }

    public function delete(int $id): void
    {
        CircuitOverseerParkingRequirement::query()->whereKey($id)->delete();

        try {
            Flux::toast(__('reports.co_toast_deleted'));
        } catch (\Throwable) {
            session()->flash('status', __('reports.co_toast_deleted'));
        }
    }

    public function render()
    {
        $query = CircuitOverseerParkingRequirement::query()->orderBy('first_name');

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where('first_name', 'like', $term);
        }

        return view('livewire.admin.circuit-overseer-parking', [
            'rows' => $query->paginate(15),
        ]);
    }
}
