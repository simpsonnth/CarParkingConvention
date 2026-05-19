<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Route as RouteFacade;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PublicRoutesList extends Component
{
    /**
     * @return list<array{label: string, route_name: string, path: string, url: string}>
     */
    public function routeEntries(): array
    {
        $definitions = [
            ['route' => 'home', 'params' => [], 'label_key' => 'routes_list.row_home'],
            ['route' => 'parking.register', 'params' => [], 'label_key' => 'routes_list.row_parking'],
            ['route' => 'parking.register-simple', 'params' => [], 'label_key' => 'routes_list.row_register_simple'],
            ['route' => 'parking.register-circuit-overseer', 'params' => [], 'label_key' => 'routes_list.row_co'],
            ['route' => 'parking.congregation-portal', 'params' => [], 'label_key' => 'routes_list.row_congregation_portal'],
            ['route' => 'locale.set', 'params' => ['locale' => 'en'], 'label_key' => 'routes_list.row_locale'],
            ['route' => 'login', 'params' => [], 'label_key' => 'routes_list.row_login'],
            ['route' => 'password.request', 'params' => [], 'label_key' => 'routes_list.row_forgot_password'],
        ];

        $rows = [];
        foreach ($definitions as $def) {
            if (! RouteFacade::has($def['route'])) {
                continue;
            }
            $url = route($def['route'], $def['params']);
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $query = parse_url($url, PHP_URL_QUERY);
            if ($query) {
                $path .= '?'.$query;
            }

            $rows[] = [
                'label' => __($def['label_key']),
                'route_name' => $def['route'],
                'path' => $path,
                'url' => $url,
            ];
        }

        return $rows;
    }

    public function render()
    {
        return view('livewire.admin.public-routes-list', [
            'entries' => $this->routeEntries(),
        ]);
    }
}
