<?php

declare(strict_types=1);

use App\Models\User;

test('attendant can access scanner but not admin users page', function () {
    $attendant = User::factory()->attendant()->create();

    $this->actingAs($attendant)
        ->get(route('attendant.scan'))
        ->assertOk();

    $this->actingAs($attendant)
        ->get(route('admin.users'))
        ->assertForbidden();

    $this->actingAs($attendant)
        ->get(route('admin.registrations'))
        ->assertForbidden();
});

test('admin retains access to admin pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.registrations'))
        ->assertOk();
});

test('attendant granted registrations view permission can view but not manage trash', function () {
    $attendant = User::factory()->attendant()->create();
    $attendant->givePermissionTo('registrations.view');

    $this->actingAs($attendant)
        ->get(route('admin.registrations'))
        ->assertOk();

    $this->actingAs($attendant)
        ->get(route('admin.registrations.trash'))
        ->assertForbidden();
});

test('admin can access scanner and dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('attendant.scan'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk();
});
