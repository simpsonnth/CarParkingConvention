<?php

use App\Livewire\Admin\PublicRoutesList;
use App\Models\User;
use App\Support\PublicRouteAccess;
use Livewire\Livewire;

test('public routes are open by default', function () {
    expect(PublicRouteAccess::isEnabled('parking.register'))->toBeTrue();
});

test('closed parking registration shows public closed message', function () {
    PublicRouteAccess::setEnabled('parking.register', false);

    $this->get(route('parking.register'))
        ->assertOk()
        ->assertSee(__('routes_list.closed_heading'))
        ->assertSee(__('routes_list.closed_parking_register'))
        ->assertDontSee(__('register.title'));
});

test('reopened parking registration shows registration form', function () {
    PublicRouteAccess::setEnabled('parking.register', true);

    $this->get(route('parking.register'))
        ->assertOk()
        ->assertSee(__('register.title'));
});

test('admin routes list shows public access controls', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(PublicRoutesList::class)
        ->assertSee(__('routes_list.title'))
        ->assertSee(__('routes_list.col_public_access'))
        ->assertSee(__('routes_list.status_always_on'));
});

test('admin can close and reopen a public route from routes list', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    PublicRouteAccess::setEnabled('parking.register', true);

    Livewire::actingAs($admin)
        ->test(PublicRoutesList::class)
        ->call('toggleRoute', 'parking.register');

    expect(PublicRouteAccess::isEnabled('parking.register'))->toBeFalse();

    $this->get(route('parking.register'))
        ->assertSee(__('routes_list.closed_parking_register'));

    Livewire::actingAs($admin)
        ->test(PublicRoutesList::class)
        ->call('toggleRoute', 'parking.register');

    expect(PublicRouteAccess::isEnabled('parking.register'))->toBeTrue();
});

test('admin cannot toggle always-on routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(PublicRoutesList::class)
        ->call('toggleRoute', 'login');

    expect(PublicRouteAccess::isEnabled('login'))->toBeTrue();
});
