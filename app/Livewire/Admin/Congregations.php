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

    public $search = '';
    public $filterCarParkId = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterCarParkId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Congregation::with('carPark')
            ->withCount([
                'parkingPasses as parked_count' => function ($query) {
                    $query->where('status', 'parked');
                }
            ]);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->filterCarParkId) {
            $query->where('car_park_id', $this->filterCarParkId);
        }

        return view('livewire.admin.congregations', [
            'congregations' => $query->paginate(10),
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
        $this->carParkId = $congregation->car_park_id;
        $this->modalOpen = true;
    }

    public function save()
    {
        $this->code = trim($this->code);

        $rules = [
            'name' => 'required|string|max:255',
            'carParkId' => 'required|exists:car_parks,id',
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
