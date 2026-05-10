<?php

use App\Livewire\Admin\PublicRoutesList;
use App\Models\User;
use Livewire\Livewire;

test('guest cannot access admin routes list page', function () {
    $this->get(route('admin.routes-list'))->assertRedirect();
});

test('admin can view routes list page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(PublicRoutesList::class)
        ->assertSee(__('routes_list.title'))
        ->assertSee(__('routes_list.row_parking'));
});
