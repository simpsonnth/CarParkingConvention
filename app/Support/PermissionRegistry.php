<?php

declare(strict_types=1);

namespace App\Support;

final class PermissionRegistry
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_merge(...array_values(self::groups()));
    }

    /**
     * Permissions assignable to attendants beyond the role default (scan.access).
     *
     * @return list<string>
     */
    public static function assignableToAttendant(): array
    {
        return array_values(array_filter(
            self::all(),
            fn (string $permission): bool => $permission !== 'scan.access'
        ));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function groups(): array
    {
        return [
            'Scanning' => [
                'scan.access',
            ],
            'Dashboard' => [
                'dashboard.view',
            ],
            'Car Parks' => [
                'car-parks.view',
                'car-parks.manage',
            ],
            'Congregations' => [
                'congregations.view',
                'congregations.manage',
            ],
            'Users' => [
                'users.manage',
            ],
            'Registrations' => [
                'registrations.view',
                'registrations.manage',
                'registrations.export',
                'registrations.print',
            ],
            'Extras' => [
                'extras.view',
                'extras.manage',
            ],
            'Coaches' => [
                'coaches.view',
                'coaches.manage',
                'coaches.export',
            ],
            'Congregation Numbers' => [
                'congregation-numbers.view',
                'congregation-numbers.manage',
            ],
            'Settings' => [
                'settings.manage',
            ],
            'Reports' => [
                'reports.view',
            ],
            'Parking Incidents' => [
                'parking-incidents.view',
                'parking-incidents.manage',
            ],
            'Toolbox Feedback' => [
                'toolbox-feedback.view',
                'toolbox-feedback.manage',
            ],
            'Lessons Learned' => [
                'lessons-learned.view',
                'lessons-learned.manage',
            ],
            'Ticket Change Requests' => [
                'ticket-change-requests.view',
                'ticket-change-requests.manage',
            ],
            'Hotel Guest Parking' => [
                'hotel-guest-parking.view',
                'hotel-guest-parking.manage',
            ],
            'Outbound Emails' => [
                'outbound-emails.view',
            ],
            'Parking QR Codes' => [
                'parking-qr.view',
            ],
            'Routes' => [
                'routes.view',
            ],
        ];
    }

    public static function label(string $permission): string
    {
        return str($permission)
            ->replace(['.', '-'], ' ')
            ->title()
            ->toString();
    }
}
