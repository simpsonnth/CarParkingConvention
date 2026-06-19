<?php

use App\Livewire\Admin\GenericParkingQrCodes;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\User;
use Livewire\Livewire;

test('guest cannot access parking qr codes admin page', function () {
    $this->get(route('admin.parking-qr-codes'))->assertRedirect();
});

test('non-admin cannot access parking qr codes admin page', function () {
    $user = User::factory()->attendant()->create();

    $this->actingAs($user)
        ->get(route('admin.parking-qr-codes'))
        ->assertForbidden();
});

test('admin can view parking qr codes page with walk-in url', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'QR Park',
        'capacity' => 30,
        'location' => 'North',
        'color' => '#7c3aed',
    ]);

    Congregation::query()->create([
        'name' => 'QR Hall',
        'uuid' => 'qr-hall-code',
        'car_park_id' => $park->id,
    ]);

    $walkInUrl = route('attendant.scan.walk-in');
    $coachWalkInUrl = route('attendant.scan.walk-in.coach');

    $this->actingAs($admin)
        ->get(route('admin.parking-qr-codes'))
        ->assertOk()
        ->assertSee(__('parking_qr.title'))
        ->assertSee($walkInUrl)
        ->assertSee($coachWalkInUrl)
        ->assertSee(__('parking_qr.coach_walk_in_heading'))
        ->assertSee('QR Hall');

    Livewire::actingAs($admin)
        ->test(GenericParkingQrCodes::class)
        ->set('search', 'QR Hall')
        ->assertSee('QR Hall')
        ->assertSee('qr-hall-code');
});

test('admin print walk-in poster contains walk-in scan url', function () {
    $admin = User::factory()->admin()->create();

    $walkInUrl = urlencode(route('attendant.scan.walk-in'));

    $this->actingAs($admin)
        ->get(route('admin.parking-qr-codes.print-walk-in'))
        ->assertOk()
        ->assertSee(__('parking_qr.poster_title'))
        ->assertSee($walkInUrl);
});

test('admin print coach walk-in poster contains coach walk-in scan url', function () {
    $admin = User::factory()->admin()->create();

    $coachWalkInUrl = urlencode(route('attendant.scan.walk-in.coach'));

    $this->actingAs($admin)
        ->get(route('admin.parking-qr-codes.print-walk-in-coach'))
        ->assertOk()
        ->assertSee(__('parking_qr.poster_coach_title'))
        ->assertSee($coachWalkInUrl);
});
