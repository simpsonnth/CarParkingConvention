<?php

declare(strict_types=1);

use App\Livewire\Admin\Registrations;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

test('congregation filter for circuit overseer matches is_circuit_overseer registrations', function () {
    $admin = User::factory()->admin()->create();

    Congregation::query()->create([
        'name' => 'Circuit Overseers',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Colin Ahaneku',
        'congregation' => 'Circuit Overseer',
        'is_circuit_overseer' => true,
        'contact_number' => '07700000001',
        'email' => 'colin@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'CO01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Regular Publisher',
        'congregation' => 'Ashford, Conningbrook',
        'is_circuit_overseer' => false,
        'contact_number' => '07700000002',
        'email' => 'pub@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'REG01BBB',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftCongregations', ['Circuit Overseer'])
        ->call('applyFilters')
        ->assertSee('Colin Ahaneku')
        ->assertDontSee('Regular Publisher');
});

test('legacy circuit overseers congregation filter label still matches circuit overseer registrations', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Gary White',
        'congregation' => 'Circuit Overseer',
        'is_circuit_overseer' => true,
        'contact_number' => '07700000003',
        'email' => 'gary@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'CO02CCC',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftCongregations', ['Circuit Overseers'])
        ->call('applyFilters')
        ->assertSee('Gary White');
});
