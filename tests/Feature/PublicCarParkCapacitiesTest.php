<?php

declare(strict_types=1);

use App\Livewire\Public\CarParkCapacities;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

test('home page links to car park capacities below login for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Log In')
        ->assertSee('Car Park Current Capacities')
        ->assertSee(route('parking.capacities'), false);
});

test('guests can view live car park capacities only', function () {
    $park = CarPark::query()->create([
        'name' => 'Public Live Park',
        'capacity' => 10,
        'capacity_friday' => 10,
        'capacity_saturday' => 10,
        'capacity_sunday' => 10,
        'overflow_capacity' => 4,
        'color' => '#22c55e',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Public Live Hall',
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    ParkingPass::query()->create([
        'congregation_id' => $congregation->id,
        'car_park_id' => $park->id,
        'status' => 'parked',
        'vehicle_reg' => 'LIVE001',
        'scanned_at' => now(),
    ]);

    foreach (range(1, 5) as $i) {
        ParkingRegistration::query()->create([
            'name' => "Driver {$i}",
            'congregation' => $congregation->name,
            'contact_number' => "077000000{$i}",
            'email' => "driver{$i}@public.test",
            'vehicle_type' => 'car',
            'vehicle_registration' => sprintf('PL%02dAAA', $i),
            'days' => ['Friday'],
        ]);
    }

    Livewire::test(CarParkCapacities::class)
        ->assertSee('Car Park Current Capacities')
        ->assertSee('Public Live Park')
        ->assertSee('1 in / 10')
        ->assertSee('Log in')
        ->assertSee('Live only')
        ->assertDontSee('5 / 10')
        ->assertDontSee('Friday');
});

test('authenticated users see expected day demand on capacities page', function () {
    $user = User::factory()->create();

    $park = CarPark::query()->create([
        'name' => 'Auth Expected Park',
        'capacity' => 10,
        'capacity_friday' => 10,
        'capacity_saturday' => 10,
        'capacity_sunday' => 10,
        'overflow_capacity' => 4,
        'color' => '#2563eb',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Auth Expected Hall',
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    foreach (range(1, 3) as $i) {
        ParkingRegistration::query()->create([
            'name' => "Driver {$i}",
            'congregation' => $congregation->name,
            'contact_number' => "077000001{$i}",
            'email' => "auth{$i}@public.test",
            'vehicle_type' => 'car',
            'vehicle_registration' => sprintf('AE%02dAAA', $i),
            'days' => ['Friday'],
        ]);
    }

    Livewire::actingAs($user)
        ->test(CarParkCapacities::class)
        ->assertSee('Auth Expected Park')
        ->assertSee('Friday')
        ->assertSee('Saturday')
        ->assertSee('Sunday')
        ->assertSee('3 / 10')
        ->assertSee('Auto-refresh 30s')
        ->assertDontSee('Live only');
});
