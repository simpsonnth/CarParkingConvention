<?php

use App\Livewire\Admin\Coaches;
use App\Livewire\Admin\Registrations;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

test('guest cannot access coaches page', function () {
    $this->get(route('admin.coaches'))->assertRedirect();
});

test('non-admin cannot access coaches page', function () {
    $user = User::factory()->attendant()->create();

    $this->actingAs($user)
        ->get(route('admin.coaches'))
        ->assertForbidden();
});

test('admin coaches page lists only coach registrations', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Car Driver',
        'congregation' => 'Alpha Hall',
        'contact_number' => '07700000001',
        'email' => 'car@alpha.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Coach Captain',
        'congregation' => 'Beta Hall',
        'contact_number' => '07700000002',
        'email' => 'captain@beta.test',
        'vehicle_type' => 'coach',
        'coach_captain_to_be_assigned' => false,
        'days' => ['Saturday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->assertSee(__('coaches.title'))
        ->assertSee('Coach Captain')
        ->assertSee('Beta Hall')
        ->assertDontSee('Car Driver');
});

test('admin can update staying on site inline', function () {
    $admin = User::factory()->admin()->create();

    $coach = ParkingRegistration::query()->create([
        'name' => 'Captain',
        'congregation' => 'Gamma Hall',
        'contact_number' => '07700000003',
        'email' => 'captain@gamma.test',
        'vehicle_type' => 'coach',
        'coach_staying_on_site' => null,
        'days' => ['Sunday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->call('updateStayingOnSite', $coach->id, '1');

    expect($coach->fresh()->coach_staying_on_site)->toBeTrue();

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->call('updateStayingOnSite', $coach->id, '0');

    expect($coach->fresh()->coach_staying_on_site)->toBeFalse();

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->call('updateStayingOnSite', $coach->id, '');

    expect($coach->fresh()->coach_staying_on_site)->toBeNull();
});

test('admin can create a coach registration from the coaches page', function () {
    $admin = User::factory()->admin()->create();

    $park = \App\Models\CarPark::query()->create([
        'name' => 'Coach Create Park',
        'capacity' => 20,
        'location' => 'North',
        'color' => '#4338ca',
    ]);

    $congregation = \App\Models\Congregation::query()->create([
        'name' => 'Create Coach Hall',
        'uuid' => 'create-coach-hall',
        'car_park_id' => $park->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->call('create')
        ->assertSet('modalOpen', true)
        ->assertSet('editingRegistration', null)
        ->set('congregation', $congregation->name)
        ->set('name', 'New Coach Captain')
        ->set('contactNumber', '07700900444')
        ->set('email', 'captain@create.test')
        ->set('days', ['Friday', 'Sunday'])
        ->set('carParkId', (string) $park->id)
        ->set('sharingWithOtherCongregations', '0')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('modalOpen', false)
        ->assertSee('New Coach Captain')
        ->assertSee('Create Coach Hall');

    $registration = ParkingRegistration::query()
        ->where('vehicle_type', 'coach')
        ->where('name', 'New Coach Captain')
        ->first();

    expect($registration)->not->toBeNull()
        ->and($registration->congregation)->toBe($congregation->name)
        ->and($registration->days)->toBe(['Friday', 'Sunday'])
        ->and($registration->car_park_id)->toBe($park->id)
        ->and($registration->email)->toBe('captain@create.test');
});

test('admin create coach requires congregation and days', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->call('create')
        ->set('name', 'Incomplete Captain')
        ->set('congregation', '')
        ->set('contactNumber', '07700900555')
        ->set('days', [])
        ->call('save')
        ->assertHasErrors(['congregation', 'days']);

    expect(ParkingRegistration::query()->where('name', 'Incomplete Captain')->exists())->toBeFalse();
});

test('admin can remove a coach registration from the coaches page', function () {
    $admin = User::factory()->admin()->create();

    $coach = ParkingRegistration::query()->create([
        'name' => 'Remove Me',
        'congregation' => 'Remove Hall',
        'contact_number' => '07700000005',
        'email' => 'remove@hall.test',
        'vehicle_type' => 'coach',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->assertSee('Remove Me')
        ->call('delete', $coach->id)
        ->assertDontSee('Remove Me');

    expect(ParkingRegistration::query()->find($coach->id))->toBeNull();
    expect(ParkingRegistration::withTrashed()->find($coach->id)?->trashed())->toBeTrue();
});

test('admin can edit coach contact and captain tba from coaches page', function () {
    $admin = User::factory()->admin()->create();

    $coach = ParkingRegistration::query()->create([
        'name' => 'Old Name',
        'congregation' => 'Delta Hall',
        'contact_number' => '07700000004',
        'email' => 'old@delta.test',
        'vehicle_type' => 'coach',
        'coach_captain_to_be_assigned' => false,
        'sharing_with_other_congregations' => false,
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->call('edit', $coach->id)
        ->set('name', 'Secretary Name')
        ->set('contactNumber', '07700000099')
        ->set('email', 'sec@delta.test')
        ->set('coachCaptainToBeAssigned', true)
        ->set('sharingWithOtherCongregations', '1')
        ->set('sharingCongregationsNotes', 'Shared with Echo Hall')
        ->call('save')
        ->assertHasNoErrors();

    $coach->refresh();
    expect($coach->name)->toBe('Secretary Name');
    expect($coach->coach_captain_to_be_assigned)->toBeTrue();
    expect($coach->sharing_with_other_congregations)->toBeTrue();
    expect($coach->sharing_congregations_notes)->toBe('Shared with Echo Hall');
});

test('staying on site filter shows only matching coaches', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Staying Yes',
        'congregation' => 'Yes Hall',
        'contact_number' => '07700000010',
        'email' => 'yes@hall.test',
        'vehicle_type' => 'coach',
        'coach_staying_on_site' => true,
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Staying No',
        'congregation' => 'No Hall',
        'contact_number' => '07700000011',
        'email' => 'no@hall.test',
        'vehicle_type' => 'coach',
        'coach_staying_on_site' => false,
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->set('filterStayingOnSite', 'yes')
        ->assertSee('Staying Yes')
        ->assertDontSee('Staying No');
});

test('registrations admin edit saves coach captain tba for coaches', function () {
    $admin = User::factory()->admin()->create();

    $coach = ParkingRegistration::query()->create([
        'name' => 'Captain',
        'congregation' => 'Zeta Hall',
        'contact_number' => '07700000020',
        'email' => 'captain@zeta.test',
        'vehicle_type' => 'coach',
        'coach_captain_to_be_assigned' => false,
        'sharing_with_other_congregations' => false,
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('edit', $coach->id)
        ->set('coachCaptainToBeAssigned', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($coach->fresh()->coach_captain_to_be_assigned)->toBeTrue();
});

test('admin can export coaches spreadsheet', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Export Coach',
        'congregation' => 'Export Hall',
        'contact_number' => '07700000030',
        'email' => 'export@hall.test',
        'vehicle_type' => 'coach',
        'days' => ['Friday'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.coaches.export'))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('coaches page shows total registrations and unique coaches after mutual survey sharing', function () {
    $admin = User::factory()->admin()->create();

    $halls = collect([
        'Share Alpha',
        'Share Beta',
        'Share Gamma',
        'Share Delta',
        'Share Echo',
        'Solo Foxtrot',
    ])->map(fn (string $name) => \App\Models\Congregation::query()->create([
        'name' => $name,
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]));

    [$alpha, $beta, $gamma, $delta, $echo, $foxtrot] = $halls->all();

    $sharedIds = $halls->take(5)->pluck('id')->map(fn ($id) => (int) $id)->all();

    foreach ($halls->take(5) as $congregation) {
        \App\Models\CongregationNumbersResponse::query()->create([
            'congregation_id' => $congregation->id,
            'car_park_tickets_count' => 0,
            'organizes_coach' => true,
            'sharing_coach_with_others' => true,
            'shared_with_congregation_ids' => array_values(array_filter(
                $sharedIds,
                fn (int $id): bool => $id !== (int) $congregation->id
            )),
            'coach_size' => \App\Models\CongregationNumbersResponse::COACH_SIZE_LARGE,
            'disabled_parking_required' => false,
        ]);

        ParkingRegistration::query()->create([
            'name' => 'Shared Captain '.$congregation->name,
            'congregation' => $congregation->name,
            'contact_number' => '07700000100',
            'email' => 'shared@'.$congregation->id.'.test',
            'vehicle_type' => 'coach',
            'sharing_with_other_congregations' => true,
            'days' => ['Friday'],
        ]);
    }

    \App\Models\CongregationNumbersResponse::query()->create([
        'congregation_id' => $foxtrot->id,
        'car_park_tickets_count' => 0,
        'organizes_coach' => true,
        'sharing_coach_with_others' => false,
        'shared_with_congregation_ids' => [],
        'coach_size' => \App\Models\CongregationNumbersResponse::COACH_SIZE_SMALL,
        'disabled_parking_required' => false,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Solo Captain',
        'congregation' => $foxtrot->name,
        'contact_number' => '07700000101',
        'email' => 'solo@foxtrot.test',
        'vehicle_type' => 'coach',
        'sharing_with_other_congregations' => false,
        'days' => ['Saturday'],
    ]);

    $metrics = app(\App\Services\CoachRegistrationMetrics::class)->summarize();

    expect($metrics['registrations_total'])->toBe(6);
    expect($metrics['unique_coaches'])->toBe(2);

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->assertSee(__('coaches.stat_registrations_total'))
        ->assertSee(__('coaches.stat_unique_coaches'))
        ->assertSee('6')
        ->assertSee('2');
});

test('one-sided survey sharing does not merge unique coach counts', function () {
    $a = \App\Models\Congregation::query()->create([
        'name' => 'One Side A',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);
    $b = \App\Models\Congregation::query()->create([
        'name' => 'One Side B',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);

    \App\Models\CongregationNumbersResponse::query()->create([
        'congregation_id' => $a->id,
        'car_park_tickets_count' => 0,
        'organizes_coach' => true,
        'sharing_coach_with_others' => true,
        'shared_with_congregation_ids' => [(int) $b->id],
        'coach_size' => \App\Models\CongregationNumbersResponse::COACH_SIZE_LARGE,
        'disabled_parking_required' => false,
    ]);

    \App\Models\CongregationNumbersResponse::query()->create([
        'congregation_id' => $b->id,
        'car_park_tickets_count' => 0,
        'organizes_coach' => true,
        'sharing_coach_with_others' => false,
        'shared_with_congregation_ids' => [],
        'coach_size' => \App\Models\CongregationNumbersResponse::COACH_SIZE_LARGE,
        'disabled_parking_required' => false,
    ]);

    foreach ([$a, $b] as $congregation) {
        ParkingRegistration::query()->create([
            'name' => 'Captain '.$congregation->name,
            'congregation' => $congregation->name,
            'contact_number' => '07700000200',
            'email' => 'side@'.$congregation->id.'.test',
            'vehicle_type' => 'coach',
            'days' => ['Friday'],
        ]);
    }

    $metrics = app(\App\Services\CoachRegistrationMetrics::class)->summarize();

    expect($metrics['registrations_total'])->toBe(2);
    expect($metrics['unique_coaches'])->toBe(2);
});

test('coach coverage reports missing and unexpected against expected spreadsheet list', function () {
    config([
        'expected_coach_congregations' => [
            'Alton',
            'Reigate',
            'Yateley',
        ],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Alton Captain',
        'congregation' => 'Alton',
        'contact_number' => '07700000300',
        'email' => 'alton@test',
        'vehicle_type' => 'coach',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Extra Captain',
        'congregation' => 'Unexpected Hall',
        'contact_number' => '07700000301',
        'email' => 'extra@test',
        'vehicle_type' => 'coach',
        'days' => ['Friday'],
    ]);

    $coverage = app(\App\Services\CoachCoverageReport::class)->summarize();

    expect($coverage['expected_total'])->toBe(3);
    expect($coverage['registered_expected'])->toBe(1);
    expect($coverage['missing'])->toBe(['Reigate', 'Yateley']);
    expect($coverage['unexpected'])->toBe(['Unexpected Hall']);
    expect($coverage['covered_via_sharing'])->toBe([]);

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->assertSee(__('coaches.coverage_missing_title'))
        ->assertSee('Reigate')
        ->assertSee('Yateley')
        ->assertSee('Unexpected Hall');
});

test('coach coverage treats survey sharing partners as covered without their own registration', function () {
    config([
        'expected_coach_congregations' => [
            'Deal',
            'Alton',
        ],
    ]);

    $deal = \App\Models\Congregation::query()->create([
        'name' => 'Deal',
        'uuid' => 'deal-uuid',
    ]);
    $walmer = \App\Models\Congregation::query()->create([
        'name' => 'Walmer',
        'uuid' => 'walmer-uuid',
    ]);

    \App\Models\CongregationNumbersResponse::query()->create([
        'congregation_id' => $deal->id,
        'car_park_tickets_count' => 0,
        'organizes_coach' => true,
        'sharing_coach_with_others' => true,
        'shared_with_congregation_ids' => [(int) $walmer->id],
        'coach_size' => \App\Models\CongregationNumbersResponse::COACH_SIZE_LARGE,
        'disabled_parking_required' => false,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Walmer Captain',
        'congregation' => 'Walmer',
        'contact_number' => '07700000320',
        'email' => 'walmer@test',
        'vehicle_type' => 'coach',
        'days' => ['Friday'],
    ]);

    $coverage = app(\App\Services\CoachCoverageReport::class)->summarize();

    expect($coverage['registered_expected'])->toBe(1);
    expect($coverage['missing'])->toBe(['Alton']);
    expect($coverage['covered_via_sharing'])->toBe([
        [
            'name' => 'Deal',
            'partners' => ['Walmer'],
        ],
    ]);
    expect($coverage['unexpected'])->toBe(['Walmer']);

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->assertSee(__('coaches.coverage_via_sharing_title'))
        ->assertSee('Deal')
        ->assertSee('Sharing with Walmer')
        ->assertSee('Alton')
        ->assertSee('Walmer');
});

test('coaches page shows survey sharing partner congregation names', function () {
    $admin = User::factory()->admin()->create();

    $alpha = \App\Models\Congregation::query()->create([
        'name' => 'Partner Alpha',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);
    $beta = \App\Models\Congregation::query()->create([
        'name' => 'Partner Beta',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);

    \App\Models\CongregationNumbersResponse::query()->create([
        'congregation_id' => $alpha->id,
        'car_park_tickets_count' => 0,
        'organizes_coach' => true,
        'sharing_coach_with_others' => true,
        'shared_with_congregation_ids' => [(int) $beta->id],
        'coach_size' => \App\Models\CongregationNumbersResponse::COACH_SIZE_SMALL,
        'disabled_parking_required' => false,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Shared Captain',
        'congregation' => $alpha->name,
        'contact_number' => '07700000310',
        'email' => 'shared@alpha.test',
        'vehicle_type' => 'coach',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Coaches::class)
        ->assertSee('Partner Alpha')
        ->assertSee('Partner Beta');
});
