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
    $admin = User::factory()->admin()->create();

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
        ->assertSee(__('print_pass.staff_manual_code_label'))
        ->assertSee('ticket-'.$registration->id)
        ->assertSee(__('print_pass.staff_manual_code_hint'))
        ->assertSee(__('print_pass.safety_notice'))
        ->assertSee(__('print_pass.display_on_dashboard'))
        ->assertSee(urlencode(route('attendant.scan.ticket', $registration)));
});

test('registration print page uses registration car park override for band colour', function () {
    $admin = User::factory()->admin()->create();

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
    $admin = User::factory()->admin()->create();

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

test('registration print page includes back page with registrant details, notes, and scripture', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Map Park',
        'capacity' => 20,
        'location' => 'East gate',
        'color' => '#dc2626',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Delta Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Back Page Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000099',
        'email' => 'backpage@delta.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'BP12AGE',
        'days' => ['Friday'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSeeHtml('class="pass-back-outer"')
        ->assertSeeHtml('class="pass-outer pass-outer--front"')
        ->assertSee(__('print_pass.back_heading'))
        ->assertSee('Back Page Driver')
        ->assertSee('07700000099')
        ->assertSee(__('print_pass.emergency_contact_note'))
        ->assertSee(__('print_pass.requested_days'))
        ->assertSee(trans_choice('print_pass.requested_days_value', 1, [
            'count' => 1,
            'days' => 'Friday',
        ]))
        ->assertSee(__('print_pass.requested_days_incorrect_note'))
        ->assertSee(__('print_pass.ticket_unique_note'))
        ->assertSee(__('print_pass.ticket_unused_note'))
        ->assertSee(__('print_pass.parking_attendants_patience_note'))
        ->assertSee(__('print_pass.footwear_note'))
        ->assertSee(__('print_pass.water_note'))
        ->assertSee(__('print_pass.scripture_reference'))
        ->assertSee(__('print_pass.scripture_text'))
        ->assertSee(__('print_pass.closing'))
        ->assertSee(__('print_pass.map_unavailable'));
});

test('registration print page shows multi-day requested days count and names', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Multi Day Park',
        'capacity' => 20,
        'location' => 'East gate',
        'color' => '#dc2626',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Multi Day Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Multi Day Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000088',
        'email' => 'multiday@hall.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'MD12DAY',
        'days' => ['Sunday', 'Friday'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSee(trans_choice('print_pass.requested_days_value', 2, [
            'count' => 2,
            'days' => 'Friday, Sunday',
        ]))
        ->assertSee(__('print_pass.requested_days_incorrect_note'));
});

test('registration print page shows none recorded when days are empty', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Empty Days Park',
        'capacity' => 20,
        'location' => 'West gate',
        'color' => '#dc2626',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Empty Days Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Empty Days Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000077',
        'email' => 'emptydays@hall.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'ED12DAY',
        'days' => [],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSee(__('print_pass.requested_days_none'))
        ->assertSee(__('print_pass.requested_days_incorrect_note'));
});

test('registration print page shows car park map image on back when uploaded', function () {
    $admin = User::factory()->admin()->create();

    \Illuminate\Support\Facades\Storage::fake('public');
    $storedPath = 'car-park-maps/test-map.jpg';
    \Illuminate\Support\Facades\Storage::disk('public')->put($storedPath, 'fake-image-content');

    $park = CarPark::query()->create([
        'name' => 'Mapped Park',
        'capacity' => 20,
        'location' => 'West lot',
        'color' => '#2563eb',
        'map_image_path' => '/storage/'.$storedPath,
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Epsilon Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Mapped Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000100',
        'email' => 'mapped@epsilon.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'MP12MAP',
        'days' => ['Saturday'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSeeHtml('class="pass-back-map"')
        ->assertSee('/storage/'.$storedPath)
        ->assertDontSee(__('print_pass.map_unavailable'));
});

test('registration print page shows travel directions beside the map', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Directions Map Park',
        'capacity' => 20,
        'location' => 'North entrance',
        'color' => '#dc2626',
        'map_image_path' => '/storage/car-park-maps/directions-map.jpg',
        'travel_directions' => "Enter via Gate B.\nFollow the blue signs to Zone West.",
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Directions Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Directions Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000111',
        'email' => 'directions@hall.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'DR12ECT',
        'days' => ['Friday'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSeeHtml('class="pass-back-map-row"')
        ->assertDontSeeHtml('class="pass-back-map-row pass-back-map-row--map-only"')
        ->assertDontSeeHtml('class="pass-back-map-row pass-back-map-row--directions-only"')
        ->assertSeeHtml('class="pass-back-directions"')
        ->assertSee(__('print_pass.travel_directions'))
        ->assertSee('Enter via Gate B.')
        ->assertSee('Follow the blue signs to Zone West.')
        ->assertSee('/storage/car-park-maps/directions-map.jpg');
});

test('registration print page renders safe travel direction headings and bold text', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Formatted Directions Park',
        'capacity' => 20,
        'location' => 'South entrance',
        'color' => '#4338ca',
        'travel_directions' => "## Arrival\nEnter through **Gate B**.",
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Formatted Directions Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Formatted Directions Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000115',
        'email' => 'formatted-directions@hall.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'FD12MAP',
        'days' => ['Friday'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSeeHtml('<h2>Arrival</h2>')
        ->assertSeeHtml('<strong>Gate B</strong>')
        ->assertDontSee('## Arrival')
        ->assertDontSee('**Gate B**');
});

test('registration print page uses override park travel directions', function () {
    $admin = User::factory()->admin()->create();

    $defaultPark = CarPark::query()->create([
        'name' => 'Default Directions Park',
        'capacity' => 20,
        'location' => 'Default',
        'color' => '#22c55e',
        'travel_directions' => 'Default park directions only',
    ]);

    $overridePark = CarPark::query()->create([
        'name' => 'Override Directions Park',
        'capacity' => 10,
        'location' => 'Override',
        'color' => '#2563eb',
        'travel_directions' => 'Override park directions only',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Override Directions Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $defaultPark->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Override Directions Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000112',
        'email' => 'override-directions@hall.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'OV12RID',
        'days' => ['Saturday'],
        'car_park_id' => $overridePark->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSee('Override park directions only')
        ->assertDontSee('Default park directions only');
});

test('registration print page escapes travel directions and omits directions panel when empty', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Escaped Directions Park',
        'capacity' => 20,
        'location' => 'West',
        'color' => '#0ea5e9',
        'travel_directions' => '<script>alert("xss")</script>',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Escaped Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Escaped Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000113',
        'email' => 'escaped@hall.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'ES12CAP',
        'days' => ['Sunday'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $registration))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert("xss")&lt;/script&gt;', false)
        ->assertDontSee('<script>alert("xss")</script>', false);

    $emptyPark = CarPark::query()->create([
        'name' => 'No Directions Park',
        'capacity' => 20,
        'location' => 'East',
        'color' => '#64748b',
        'map_image_path' => '/storage/car-park-maps/no-directions.jpg',
    ]);

    $emptyCongregation = Congregation::query()->create([
        'name' => 'No Directions Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $emptyPark->id,
    ]);

    $emptyRegistration = ParkingRegistration::query()->create([
        'name' => 'No Directions Driver',
        'congregation' => $emptyCongregation->name,
        'contact_number' => '07700000114',
        'email' => 'nodirections@hall.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'ND12MAP',
        'days' => ['Friday'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.registrations.print', $emptyRegistration))
        ->assertOk()
        ->assertSeeHtml('class="pass-back-map-row pass-back-map-row--map-only"')
        ->assertDontSeeHtml('class="pass-back-directions"');
});
