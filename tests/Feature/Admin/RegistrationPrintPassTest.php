<?php

use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Models\User;

test('guest cannot access registration print page', function () {
    $registration = ParkingRegistration::query()->create([
        'name' => 'Driver One',
        'congregation' => 'Alpha Hall',
        'contact_number' => '07700000001',
        'email' => 'driver@alpha.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
    ]);

    $this->get(route('admin.registrations.print', $registration))->assertRedirect();
});

test('registration print page uses landscape layout with car park band, hero congregation, ticket number, and safety notice', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $park = CarPark::query()->create([
        'name' => 'Red Car Park A',
        'capacity' => 20,
        'location' => 'North side',
        'color' => '#dc2626',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Alpha Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Driver One',
        'congregation' => $congregation->name,
        'contact_number' => '07700000001',
        'email' => 'driver@alpha.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
    ]);

    $ticketNumber = str_pad((string) $registration->id, 6, '0', STR_PAD_LEFT);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSee('Red Car Park A')
        ->assertSee('Alpha Hall')
        ->assertSee(__('print_pass.organization'))
        ->assertSeeHtml('size: A4 landscape')
        ->assertSeeHtml('class="pass-top-org')
        ->assertSeeHtml('class="pass-zone-name')
        ->assertSeeHtml('class="pass-identity-name')
        ->assertSeeHtml('class="pass-fine-print')
        ->assertSeeHtml('linear-gradient(118deg, #dc2626')
        ->assertSee(__('print_pass.ticket_number'))
        ->assertSee($ticketNumber)
        ->assertSee(__('print_pass.safety_notice'))
        ->assertSee(__('print_pass.display_on_dashboard'))
        ->assertSee(urlencode(route('attendant.scan.ticket', $registration)));
});

test('registration print page uses registration car park override for band colour', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $defaultPark = CarPark::query()->create([
        'name' => 'Default Park',
        'capacity' => 20,
        'location' => 'Default',
        'color' => '#22c55e',
    ]);

    $overridePark = CarPark::query()->create([
        'name' => 'Override Park',
        'capacity' => 10,
        'location' => 'Override',
        'color' => '#2563eb',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Beta Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $defaultPark->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Override Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000002',
        'email' => 'override@beta.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'XY99ZZZ',
        'days' => ['Saturday'],
        'car_park_id' => $overridePark->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSee('Override Park')
        ->assertSeeHtml('linear-gradient(118deg, #2563eb')
        ->assertDontSeeHtml('linear-gradient(118deg, #22c55e');
});

test('registration print page shows coach alert strip', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $park = CarPark::query()->create([
        'name' => 'Coach Park',
        'capacity' => 10,
        'location' => 'South',
        'color' => '#dc2626',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Gamma Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Coach Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000003',
        'email' => 'coach@gamma.test',
        'vehicle_type' => 'coach',
        'vehicle_registration' => 'CO01ACH',
        'days' => ['Friday'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSeeHtml('class="pass-alert pass-alert--coach"')
        ->assertSee(__('print_pass.ticket_for_coach_space'));
});
