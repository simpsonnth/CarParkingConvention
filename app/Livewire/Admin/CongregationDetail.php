<?php

namespace App\Livewire\Admin;

use App\Models\Congregation;
use App\Models\ParkingPass;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Flux\Flux;

class CongregationDetail extends Component
{
    use WithPagination;

    public Congregation $congregation;

    public function mount(Congregation $congregation)
    {
        $this->congregation = $congregation;
    }

    public function checkout($passId)
    {
        $pass = ParkingPass::findOrFail($passId);
        $pass->update([
            'status' => 'left',
            'left_at' => now(),
        ]);
        Flux::toast('Vehicle checked out.');
    }

    public function checkoutAll()
    {
        ParkingPass::where('congregation_id', $this->congregation->id)
            ->where('status', 'parked')
            ->update([
                'status' => 'left',
                'left_at' => now(),
            ]);

        Flux::toast('All vehicles checked out.');
    }

    public function render()
    {
        $cars = ParkingPass::where('congregation_id', $this->congregation->id)
            ->where('status', 'parked')
            ->with(['scannedBy', 'congregation.carPark'])
            ->latest('scanned_at')
            ->paginate(15);

        return view('livewire.admin.congregation-detail', [
            'cars' => $cars
        ]);
    }
}
