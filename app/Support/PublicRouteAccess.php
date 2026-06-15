<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Route as RouteFacade;

class PublicRouteAccess
{
    private const SETTING_KEY = 'public_route_enabled';

    /**
     * @return list<array{
     *     route: string,
     *     params: array<string, mixed>,
     *     label_key: string,
     *     closed_message_key: string,
     *     toggleable: bool,
     * }>
     */
    public static function definitions(): array
    {
        return [
            [
                'route' => 'home',
                'params' => [],
                'label_key' => 'routes_list.row_home',
                'closed_message_key' => 'routes_list.closed_default',
                'toggleable' => false,
            ],
            [
                'route' => 'parking.register',
                'params' => [],
                'label_key' => 'routes_list.row_parking',
                'closed_message_key' => 'routes_list.closed_parking_register',
                'toggleable' => true,
            ],
            [
                'route' => 'parking.register-simple',
                'params' => [],
                'label_key' => 'routes_list.row_register_simple',
                'closed_message_key' => 'routes_list.closed_register_simple',
                'toggleable' => true,
            ],
            [
                'route' => 'parking.register-circuit-overseer',
                'params' => [],
                'label_key' => 'routes_list.row_co',
                'closed_message_key' => 'routes_list.closed_register_circuit_overseer',
                'toggleable' => true,
            ],
            [
                'route' => 'parking.congregation-portal',
                'params' => [],
                'label_key' => 'routes_list.row_congregation_portal',
                'closed_message_key' => 'routes_list.closed_congregation_portal',
                'toggleable' => true,
            ],
            [
                'route' => 'management.parking-incidents',
                'params' => [],
                'label_key' => 'routes_list.row_parking_incidents',
                'closed_message_key' => 'routes_list.closed_parking_incidents',
                'toggleable' => true,
            ],
            [
                'route' => 'management.toolbox-feedback',
                'params' => [],
                'label_key' => 'routes_list.row_toolbox_feedback',
                'closed_message_key' => 'routes_list.closed_toolbox_feedback',
                'toggleable' => true,
            ],
            [
                'route' => 'management.lessons-learned',
                'params' => [],
                'label_key' => 'routes_list.row_lessons_learned',
                'closed_message_key' => 'routes_list.closed_lessons_learned',
                'toggleable' => true,
            ],
            [
                'route' => 'locale.set',
                'params' => ['locale' => 'en'],
                'label_key' => 'routes_list.row_locale',
                'closed_message_key' => 'routes_list.closed_default',
                'toggleable' => false,
            ],
            [
                'route' => 'login',
                'params' => [],
                'label_key' => 'routes_list.row_login',
                'closed_message_key' => 'routes_list.closed_default',
                'toggleable' => false,
            ],
            [
                'route' => 'password.request',
                'params' => [],
                'label_key' => 'routes_list.row_forgot_password',
                'closed_message_key' => 'routes_list.closed_default',
                'toggleable' => false,
            ],
        ];
    }

    public static function definitionFor(string $routeName): ?array
    {
        foreach (self::definitions() as $definition) {
            if ($definition['route'] === $routeName) {
                return $definition;
            }
        }

        return null;
    }

    public static function isToggleable(string $routeName): bool
    {
        return (bool) (self::definitionFor($routeName)['toggleable'] ?? false);
    }

    public static function isEnabled(string $routeName): bool
    {
        if (! self::isToggleable($routeName)) {
            return true;
        }

        $states = self::enabledStates();

        return (bool) ($states[$routeName] ?? true);
    }

    /**
     * @return array<string, bool>
     */
    public static function enabledStates(): array
    {
        $stored = Setting::get(self::SETTING_KEY);
        $decoded = is_string($stored) ? json_decode($stored, true) : null;
        $overrides = is_array($decoded) ? $decoded : [];

        $states = [];
        foreach (self::definitions() as $definition) {
            if (! $definition['toggleable']) {
                continue;
            }

            $routeName = $definition['route'];
            $states[$routeName] = array_key_exists($routeName, $overrides)
                ? (bool) $overrides[$routeName]
                : true;
        }

        return $states;
    }

    public static function setEnabled(string $routeName, bool $enabled): void
    {
        if (! self::isToggleable($routeName)) {
            return;
        }

        $states = self::enabledStates();
        $states[$routeName] = $enabled;
        self::persistStates($states);
    }

    public static function closedMessageKey(string $routeName): string
    {
        return self::definitionFor($routeName)['closed_message_key'] ?? 'routes_list.closed_default';
    }

    /**
     * @return list<array{
     *     label: string,
     *     route_name: string,
     *     path: string,
     *     url: string,
     *     toggleable: bool,
     *     enabled: bool,
     * }>
     */
    public static function routeEntries(): array
    {
        $states = self::enabledStates();
        $rows = [];

        foreach (self::definitions() as $definition) {
            if (! RouteFacade::has($definition['route'])) {
                continue;
            }

            $url = route($definition['route'], $definition['params']);
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $query = parse_url($url, PHP_URL_QUERY);
            if ($query) {
                $path .= '?'.$query;
            }

            $routeName = $definition['route'];

            $rows[] = [
                'label' => __($definition['label_key']),
                'route_name' => $routeName,
                'path' => $path,
                'url' => $url,
                'toggleable' => $definition['toggleable'],
                'enabled' => $definition['toggleable']
                    ? (bool) ($states[$routeName] ?? true)
                    : true,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, bool>  $states
     */
    private static function persistStates(array $states): void
    {
        Setting::set(self::SETTING_KEY, json_encode($states));
    }
}
