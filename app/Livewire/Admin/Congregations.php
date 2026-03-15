<?php

namespace App\Livewire\Admin;

use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;
use Illuminate\Support\Str;

class Congregations extends Component
{
    use WithPagination;

    public $name = '';
    public $code = '';
    public $carParkId = '';
    public $congregationId = null;

    public $qrCodeUrl = '';
    public $qrCodeName = '';
    public $qrCodeUuid = '';
    public $qrCodeCongregationId = null;

    public bool $modalOpen = false;
    public bool $qrModalOpen = false;
    public bool $bulkAssignModalOpen = false;

    public $search = '';
    public $filterCarParkId = '';

    /** @var int Per-page options: 25, 50, 100 */
    public int $perPage = 25;

    /** @var array<int> */
    public array $selectedIds = [];

    /** For bulk assign: selected car park id (empty = unassign) */
    public $bulkAssignCarParkId = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterCarParkId()
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleSelect(int $id): void
    {
        $key = array_search($id, $this->selectedIds);
        if ($key !== false) {
            array_splice($this->selectedIds, $key, 1);
            $this->selectedIds = array_values($this->selectedIds);
        } else {
            $this->selectedIds = array_values(array_merge($this->selectedIds, [$id]));
        }
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->getCongregationsQuery()->paginate($this->perPage)->pluck('id')->all();
        if (count(array_intersect($this->selectedIds, $ids)) === count($ids)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $ids));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $ids)));
        }
    }

    public function openBulkAssignModal(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast('Please select at least one congregation.', variant: 'warning');
            return;
        }
        $this->bulkAssignCarParkId = '';
        $this->bulkAssignModalOpen = true;
    }

    public function bulkAssignCarPark(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast('Please select at least one congregation.', variant: 'warning');
            $this->bulkAssignModalOpen = false;
            return;
        }
        $carParkId = $this->bulkAssignCarParkId ?: null;
        if ($carParkId !== null) {
            $this->validate(['bulkAssignCarParkId' => 'required|exists:car_parks,id']);
        }
        $count = Congregation::whereIn('id', $this->selectedIds)->update(['car_park_id' => $carParkId]);
        $this->selectedIds = [];
        $this->bulkAssignModalOpen = false;
        $this->bulkAssignCarParkId = '';
        Flux::toast($carParkId
            ? "{$count} congregation(s) assigned to car park."
            : "{$count} congregation(s) unassigned from car park.");
    }

    protected function getCongregationsQuery()
    {
        $query = Congregation::with('carPark')
            ->withCount([
                'parkingPasses as parked_count' => function ($q) {
                    $q->where('status', 'parked');
                }
            ]);
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }
        if ($this->filterCarParkId !== '') {
            if ($this->filterCarParkId === 'unassigned') {
                $query->whereNull('car_park_id');
            } else {
                $query->where('car_park_id', $this->filterCarParkId);
            }
        }
        return $query;
    }

    public function render()
    {
        $congregations = $this->getCongregationsQuery()->paginate($this->perPage);

        return view('livewire.admin.congregations', [
            'congregations' => $congregations,
            'carParks' => CarPark::all(),
        ]);
    }

    public function create()
    {
        $this->reset('name', 'code', 'carParkId', 'congregationId');
        $this->modalOpen = true;
    }

    public function edit(Congregation $congregation)
    {
        $this->congregationId = $congregation->id;
        $this->name = $congregation->name;
        $this->code = $congregation->uuid ?? '';
        $this->carParkId = $congregation->car_park_id ?? '';
        $this->modalOpen = true;
    }

    public function save()
    {
        $this->code = trim($this->code);
        $this->carParkId = $this->carParkId ?: null;

        $rules = [
            'name' => 'required|string|max:255',
            'carParkId' => 'nullable|exists:car_parks,id',
            'code' => [
                'required',
                'string',
                'max:36',
                Rule::unique('congregations', 'uuid')->ignore($this->congregationId),
            ],
        ];

        $this->validate($rules);

        if ($this->congregationId) {
            $congregation = Congregation::findOrFail($this->congregationId);
            $congregation->update([
                'name' => $this->name,
                'uuid' => $this->code,
                'car_park_id' => $this->carParkId,
            ]);
        } else {
            Congregation::create([
                'name' => $this->name,
                'uuid' => $this->code,
                'car_park_id' => $this->carParkId,
            ]);
        }

        $this->modalOpen = false;
        Flux::toast($this->congregationId ? 'Congregation updated successfully.' : 'Congregation created successfully.');
        $this->reset('name', 'code', 'carParkId', 'congregationId');
    }

    public function delete(Congregation $congregation)
    {
        $congregation->delete();
        Flux::toast('Congregation deleted successfully.');
    }

    public function openQrModal(Congregation $congregation)
    {
        $this->qrCodeUuid = $congregation->uuid ?? '';
        $this->qrCodeName = $congregation->name;
        $this->qrCodeCongregationId = $congregation->id;
        $this->qrModalOpen = true;
    }


}
