<?php

namespace App\Livewire;

use Livewire\Component;

class SessionKeepalive extends Component
{
    public function ping(): void
    {
        // Request refreshes session last_activity via web middleware.
    }

    public function render()
    {
        return view('livewire.session-keepalive');
    }
}
