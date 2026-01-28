<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;
use Livewire\WithFileUploads;
use Flux\Flux;

class Settings extends Component
{
    use WithFileUploads;

    public $conventionName = '';
    public $conventionYear = '';
    public $conventionLocation = '';
    public $ticketLogo = null;
    public $existingLogo = '';

    public function mount()
    {
        $this->conventionName = Setting::get('convention_name', "Convention of Jehovah's Witness");
        $this->conventionYear = Setting::get('convention_year', date('Y'));
        $this->conventionLocation = Setting::get('convention_location', 'Twickenham');
        $this->existingLogo = Setting::get('ticket_logo', '');
    }

    public function save()
    {
        $this->validate([
            'conventionName' => 'required|string|max:255',
            'conventionYear' => 'required|string|max:4',
            'conventionLocation' => 'required|string|max:255',
            'ticketLogo' => 'nullable|image|max:1024', // 1MB Max
        ]);

        Setting::set('convention_name', $this->conventionName);
        Setting::set('convention_year', $this->conventionYear);
        Setting::set('convention_location', $this->conventionLocation);

        if ($this->ticketLogo) {
            $path = $this->ticketLogo->store('logos', 'public');
            Setting::set('ticket_logo', '/storage/' . $path);
            $this->existingLogo = '/storage/' . $path;
        }

        Flux::toast('Settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}
