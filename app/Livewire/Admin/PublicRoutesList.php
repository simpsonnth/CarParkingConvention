<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Support\PublicRouteAccess;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PublicRoutesList extends Component
{
    public function toggleRoute(string $routeName): void
    {
        if (! PublicRouteAccess::isToggleable($routeName)) {
            return;
        }

        $enabled = ! PublicRouteAccess::isEnabled($routeName);
        PublicRouteAccess::setEnabled($routeName, $enabled);

        Flux::toast(
            $enabled
                ? __('routes_list.toast_opened', ['page' => __(PublicRouteAccess::definitionFor($routeName)['label_key'] ?? $routeName)])
                : __('routes_list.toast_closed', ['page' => __(PublicRouteAccess::definitionFor($routeName)['label_key'] ?? $routeName)]),
            variant: $enabled ? 'success' : 'warning',
        );
    }

    public function render()
    {
        return view('livewire.admin.public-routes-list', [
            'entries' => PublicRouteAccess::routeEntries(),
        ]);
    }
}
