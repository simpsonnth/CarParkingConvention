<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Services\CongregationPortalAuth;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

class Settings extends Component
{
    use WithFileUploads;

    public $conventionName = '';

    public $conventionYear = '';

    public $conventionLocation = '';

    public $ticketLogo = null;

    public $existingLogo = '';

    public string $congregationPortalPassword = '';

    public bool $congregationPortalPasswordConfigured = false;

    public function mount()
    {
        $this->conventionName = Setting::get('convention_name', "Convention of Jehovah's Witness");
        $this->conventionYear = Setting::get('convention_year', date('Y'));
        $this->conventionLocation = Setting::get('convention_location', 'Twickenham');
        $this->existingLogo = Setting::get('ticket_logo', '');
        $this->congregationPortalPasswordConfigured = app(CongregationPortalAuth::class)->passwordIsConfigured();
    }

    public function save()
    {
        $rules = [
            'conventionName' => 'required|string|max:255',
            'conventionYear' => 'required|string|max:4',
            'conventionLocation' => 'required|string|max:255',
            'ticketLogo' => 'nullable|image|max:1024', // 1MB Max
            'congregationPortalPassword' => 'nullable|string|min:4|max:255',
        ];

        if (! $this->congregationPortalPasswordConfigured) {
            $rules['congregationPortalPassword'] = 'required|string|min:4|max:255';
        }

        $this->validate($rules);

        Setting::set('convention_name', $this->conventionName);
        Setting::set('convention_year', $this->conventionYear);
        Setting::set('convention_location', $this->conventionLocation);

        if ($this->ticketLogo) {
            $path = $this->ticketLogo->store('logos', 'public');
            Setting::set('ticket_logo', '/storage/'.$path);
            $this->existingLogo = '/storage/'.$path;
        }

        if ($this->congregationPortalPassword !== '') {
            CongregationPortalAuth::setPassword($this->congregationPortalPassword);
            $this->congregationPortalPasswordConfigured = true;
            $this->congregationPortalPassword = '';
        }

        Flux::toast('Settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}
