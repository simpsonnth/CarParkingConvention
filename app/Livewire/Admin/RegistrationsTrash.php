<?php

namespace App\Livewire\Admin;

use App\Models\ParkingRegistration;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class RegistrationsTrash extends Component
{
    use WithPagination;

    public array $selectedIds = [];

    public function getRegistrationsProperty()
    {
        return ParkingRegistration::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(15);
    }

    public function restore(int $id): void
    {
        ParkingRegistration::onlyTrashed()->findOrFail($id)->restore();
        $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
        Flux::toast(__('registrations.restored'));
    }

    public function restoreSelected(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            return;
        }
        ParkingRegistration::onlyTrashed()->whereIn('id', $this->selectedIds)->restore();
        $count = count($this->selectedIds);
        $this->selectedIds = [];
        Flux::toast(__('registrations.bulk_restored', ['count' => $count]));
    }

    public function forceDelete(int $id): void
    {
        ParkingRegistration::onlyTrashed()->findOrFail($id)->forceDelete();
        $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
        Flux::toast(__('registrations.permanently_deleted'));
    }

    public function forceDeleteSelected(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(__('registrations.select_items'), variant: 'warning');
            return;
        }
        ParkingRegistration::onlyTrashed()->whereIn('id', $this->selectedIds)->forceDelete();
        $count = count($this->selectedIds);
        $this->selectedIds = [];
        Flux::toast(__('registrations.bulk_permanently_deleted', ['count' => $count]));
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->getRegistrationsProperty()->pluck('id')->all();
        if (count(array_intersect($this->selectedIds, $ids)) === count($ids)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $ids));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $ids)));
        }
    }

    public function render()
    {
        return view('livewire.admin.registrations-trash', [
            'registrations' => $this->registrations,
        ]);
    }
}
