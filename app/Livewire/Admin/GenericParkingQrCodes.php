<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Congregation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class GenericParkingQrCodes extends Component
{
    public string $search = '';

    /**
     * @return Collection<int, Congregation>
     */
    public function congregations(): Collection
    {
        return Congregation::query()
            ->with('carPark')
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('uuid', 'like', $term);
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.generic-parking-qr-codes', [
            'congregations' => $this->congregations(),
            'walkInScanUrl' => route('attendant.scan.walk-in'),
            'coachWalkInScanUrl' => route('attendant.scan.walk-in.coach'),
            'convName' => \App\Models\Setting::get('convention_name', "Convention of Jehovah's Witness"),
            'convYear' => \App\Models\Setting::get('convention_year', date('Y')),
            'convLoc' => \App\Models\Setting::get('convention_location', 'Twickenham'),
            'ticketLogo' => \App\Models\Setting::get('ticket_logo'),
        ]);
    }
}
