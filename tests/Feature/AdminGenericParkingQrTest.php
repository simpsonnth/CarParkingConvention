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

test('guest handout defaults to rosebine 2 when present', function () {
    $admin = User::factory()->admin()->create();

    CarPark::query()->create([
        'name' => 'West',
        'capacity' => 60,
        'location' => 'West',
        'color' => '#2563eb',
        'latitude' => 51.4548895,
        'longitude' => -0.3434177,
    ]);

    $rosebine2 = CarPark::query()->create([
        'name' => 'Rosebine 2',
        'capacity' => 56,
        'location' => 'South',
        'color' => '#0f766e',
        'latitude' => 51.44958137563192,
        'longitude' => -0.3505309665999623,
        'map_image_path' => '/storage/car-park-maps/rosebine-2.png',
    ]);

    Livewire::actingAs($admin)
        ->test(GenericParkingQrCodes::class)
        ->assertSet('guestCarParkId', $rosebine2->id)
        ->assertSee(__('parking_qr.guest_heading'))
        ->assertSee(__('parking_qr.guest_label'))
        ->assertSee('Rosebine 2')
        ->assertSee(__('parking_qr.guest_map_ready'))
        ->assertSee(__('parking_qr.guest_coords_ready'));
});

test('guest handout falls back when rosebine 2 is missing', function () {
    $admin = User::factory()->admin()->create();

    $withCoords = CarPark::query()->create([
        'name' => 'North',
        'capacity' => 40,
        'location' => 'North',
        'color' => '#4338ca',
        'latitude' => 51.4579576,
        'longitude' => -0.3417655,
    ]);

    CarPark::query()->create([
        'name' => 'Alpha No Coords',
        'capacity' => 20,
        'location' => 'A',
        'color' => '#71717a',
    ]);

    Livewire::actingAs($admin)
        ->test(GenericParkingQrCodes::class)
        ->assertSet('guestCarParkId', $withCoords->id);
});

test('admin can print visiting guest handout with map and navigation qr', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Guest Print Park',
        'capacity' => 40,
        'location' => 'South',
        'color' => '#0f766e',
        'latitude' => 51.44958137563192,
        'longitude' => -0.3505309665999623,
        'map_image_path' => '/storage/car-park-maps/guest-print.png',
        'travel_directions' => 'Enter via Gate C.',
    ]);

    $navUrl = $park->navigationUrl();
    expect($navUrl)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.parking-qr-codes.print-guest', $park))
        ->assertOk()
        ->assertSee(__('parking_qr.guest_label'))
        ->assertSee('Guest Print Park')
        ->assertSee(__('parking_qr.guest_nav_label'))
        ->assertSee(urlencode($navUrl))
        ->assertSee('/storage/car-park-maps/guest-print.png')
        ->assertSee('images/guest-handout-hero.png')
        ->assertSee('https://www.jw.org/en/library/programs/2026-convention-program/')
        ->assertSee('https://www.jw.org/en/jehovahs-witnesses/faq/')
        ->assertSee('https://hub.jw.org/request-visit/en/request')
        ->assertSee(__('parking_qr.guest_map_heading'));
});

test('guest handout print shows warnings when map and coords missing', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Incomplete Guest Park',
        'capacity' => 20,
        'location' => 'Unknown',
        'color' => '#71717a',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.parking-qr-codes.print-guest', $park))
        ->assertOk()
        ->assertSee(__('parking_qr.guest_label'))
        ->assertSee(__('parking_qr.guest_map_missing_print'))
        ->assertSee(__('parking_qr.guest_coords_missing_print'));
});
