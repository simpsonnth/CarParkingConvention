<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Services\CongregationPortalAuth;
use App\Support\TicketEmailBody;
use App\Support\TicketEmailCcList;
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

    public string $ticketEmailCcs = '';

    public string $ticketEmailBody = '';

    public function mount()
    {
        $this->conventionName = Setting::get('convention_name', "Convention of Jehovah's Witness");
        $this->conventionYear = Setting::get('convention_year', date('Y'));
        $this->conventionLocation = Setting::get('convention_location', 'Twickenham');
        $this->existingLogo = Setting::get('ticket_logo', '');
        $this->congregationPortalPasswordConfigured = app(CongregationPortalAuth::class)->passwordIsConfigured();
        $this->ticketEmailCcs = (string) (Setting::get(TicketEmailCcList::SETTING_KEY) ?? '');
        $this->ticketEmailBody = TicketEmailBody::template();
    }

    public function save()
    {
        $rules = [
            'conventionName' => 'required|string|max:255',
            'conventionYear' => 'required|string|max:4',
            'conventionLocation' => 'required|string|max:255',
            'ticketLogo' => 'nullable|image|max:1024', // 1MB Max
            'congregationPortalPassword' => 'nullable|string|min:4|max:255',
            'ticketEmailCcs' => 'nullable|string|max:2000',
            'ticketEmailBody' => 'required|string|max:5000',
        ];

        if (! $this->congregationPortalPasswordConfigured) {
            $rules['congregationPortalPassword'] = 'required|string|min:4|max:255';
        }

        $this->validate($rules);

        $parsedCcs = TicketEmailCcList::parse($this->ticketEmailCcs);
        if (trim($this->ticketEmailCcs) !== '' && $parsedCcs === []) {
            $this->addError('ticketEmailCcs', 'Enter at least one valid email address, or leave blank for no CC.');

            return;
        }

        Setting::set('convention_name', $this->conventionName);
        Setting::set('convention_year', $this->conventionYear);
        Setting::set('convention_location', $this->conventionLocation);
        Setting::set(
            TicketEmailCcList::SETTING_KEY,
            TicketEmailCcList::toStorageString($this->ticketEmailCcs),
        );
        $this->ticketEmailCcs = (string) (Setting::get(TicketEmailCcList::SETTING_KEY) ?? '');

        Setting::set(TicketEmailBody::SETTING_KEY, trim($this->ticketEmailBody));
        $this->ticketEmailBody = TicketEmailBody::template();

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
