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
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.coaches'))
        ->assertRedirect(route('dashboard'));
});

test('admin coaches page lists only coach registrations', function () {
    $admin = User::factory()->create(['role' => 'admin']);

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
    $admin = User::factory()->create(['role' => 'admin']);

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

test('admin can edit coach contact and captain tba from coaches page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

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
    $admin = User::factory()->create(['role' => 'admin']);

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
    $admin = User::factory()->create(['role' => 'admin']);

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
    $admin = User::factory()->create(['role' => 'admin']);

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
