<?php

declare(strict_types=1);

use App\Exports\ParkingRegistrationsExport;
use App\Livewire\Admin\Registrations;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

test('unfiltered registrations export includes all registrations', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Alice Export',
        'congregation' => 'Ashford, Conningbrook',
        'contact_number' => '07700000001',
        'email' => 'alice@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'AL01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Bob Export',
        'congregation' => 'Other Hall',
        'contact_number' => '07700000002',
        'email' => 'bob@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'BO02BBB',
        'days' => ['Friday'],
    ]);

    Excel::fake();

    $this->actingAs($admin)
        ->get(route('admin.registrations.export'))
        ->assertOk();

    Excel::matchByRegex();
    Excel::assertDownloaded('/parking-registrations-all-.*\.xlsx/', function (ParkingRegistrationsExport $export): bool {
        return $export->query()->count() === 2;
    });
});

test('congregation filter limits registrations export rows', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Filtered Congregation Registrant',
        'congregation' => 'Ashford, Conningbrook',
        'contact_number' => '07700000001',
        'email' => 'filtered@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'FI01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Other Congregation Registrant',
        'congregation' => 'Other Hall',
        'contact_number' => '07700000002',
        'email' => 'other@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'OT02BBB',
        'days' => ['Friday'],
    ]);

    Excel::fake();

    $this->actingAs($admin)
        ->get(route('admin.registrations.export', [
            'congregations' => ['Ashford, Conningbrook'],
        ]))
        ->assertOk();

    Excel::matchByRegex();
    Excel::assertDownloaded('/parking-registrations-filtered-.*\.xlsx/', function (ParkingRegistrationsExport $export): bool {
        $rows = $export->query()->get();

        return $rows->count() === 1
            && $rows->first()?->name === 'Filtered Congregation Registrant';
    });
});

test('search filter limits registrations export rows', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Unique Search Target',
        'congregation' => 'Ashford, Conningbrook',
        'contact_number' => '07700000001',
        'email' => 'unique@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'UN01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Someone Else',
        'congregation' => 'Other Hall',
        'contact_number' => '07700000002',
        'email' => 'else@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'EL02BBB',
        'days' => ['Friday'],
    ]);

    Excel::fake();

    $this->actingAs($admin)
        ->get(route('admin.registrations.export', [
            'search' => 'Unique Search',
        ]))
        ->assertOk();

    Excel::matchByRegex();
    Excel::assertDownloaded('/parking-registrations-filtered-.*\.xlsx/', function (ParkingRegistrationsExport $export): bool {
        $rows = $export->query()->get();

        return $rows->count() === 1
            && $rows->first()?->name === 'Unique Search Target';
    });
});

test('invalid car park filter returns validation error', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.registrations'))
        ->get(route('admin.registrations.export', [
            'car_parks' => [99999],
        ]))
        ->assertRedirect(route('admin.registrations'))
        ->assertSessionHasErrors('car_parks.0');
});

test('days filter limits registrations export to exact day set', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Friday Only Export',
        'congregation' => 'Ashford, Conningbrook',
        'contact_number' => '07700000001',
        'email' => 'friday-export@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'FE01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Friday Saturday Export',
        'congregation' => 'Other Hall',
        'contact_number' => '07700000002',
        'email' => 'friday-saturday-export@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'FE02BBB',
        'days' => ['Friday', 'Saturday'],
    ]);

    Excel::fake();

    $this->actingAs($admin)
        ->get(route('admin.registrations.export', [
            'days' => ['Friday'],
        ]))
        ->assertOk();

    Excel::matchByRegex();
    Excel::assertDownloaded('/parking-registrations-filtered-.*\.xlsx/', function (ParkingRegistrationsExport $export): bool {
        $rows = $export->query()->get();

        return $rows->count() === 1
            && $rows->first()?->name === 'Friday Only Export';
    });
});

test('invalid days filter returns validation error', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.registrations'))
        ->get(route('admin.registrations.export', [
            'days' => ['Monday'],
        ]))
        ->assertRedirect(route('admin.registrations'))
        ->assertSessionHasErrors('days.0');
});

test('duplicate days filter returns validation error', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.registrations'))
        ->get(route('admin.registrations.export', [
            'days' => ['Friday', 'Friday'],
        ]))
        ->assertRedirect(route('admin.registrations'))
        ->assertSessionHasErrors('days.1');
});

test('registrations export url includes active filters and search', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('search', 'Target Name')
        ->set('filterCongregations', ['Ashford, Conningbrook'])
        ->set('filterDays', ['Friday', 'Saturday'])
        ->assertSet('exportUrl', route('admin.registrations.export', [
            'search' => 'Target Name',
            'congregations' => ['Ashford, Conningbrook'],
            'days' => ['Friday', 'Saturday'],
        ]));
});
