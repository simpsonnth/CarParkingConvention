<?php

use App\Livewire\Public\CongregationPortal;
use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use App\Services\CongregationPortalAuth;
use Illuminate\Support\Str;
use Livewire\Livewire;

function seedPortalCongregation(string $name = 'Alpha Hall', int $tickets = 5): Congregation
{
    $uuid = (string) Str::uuid();
    $cong = Congregation::query()->create(['name' => $name, 'uuid' => $uuid]);
    CongregationNumbersResponse::query()->create([
        'congregation_id' => $cong->id,
        'car_park_tickets_count' => $tickets,
        'organizes_coach' => false,
        'disabled_parking_required' => false,
        'disabled_parking_count' => 0,
    ]);

    return $cong;
}

beforeEach(function () {
    CongregationPortalAuth::setPassword('Happiness');
});

test('portal rejects invalid credentials', function () {
    $cong = seedPortalCongregation();

    Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $cong->uuid)
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('congregationCode');
});

test('portal login shows registrations without pii', function () {
    $cong = seedPortalCongregation();
    ParkingRegistration::query()->create([
        'name' => 'Secret Person',
        'congregation' => $cong->name,
        'contact_number' => '07123456789',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
        'email' => 'secret@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $cong->uuid)
        ->set('password', 'Happiness')
        ->call('login')
        ->assertSet('isAuthenticated', true)
        ->assertSee('AB12CDE')
        ->assertDontSee('Secret Person')
        ->assertDontSee('secret@example.test')
        ->assertDontSee('07123456789');
});

test('portal cannot edit another congregations registration', function () {
    $congA = seedPortalCongregation('Hall A');
    $congB = seedPortalCongregation('Hall B');

    $other = ParkingRegistration::query()->create([
        'name' => 'Other',
        'congregation' => $congB->name,
        'contact_number' => '111',
        'vehicle_registration' => 'ZZ99ZZZ',
        'days' => ['Friday'],
        'email' => 'other@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $congA->uuid)
        ->set('password', 'Happiness')
        ->call('login')
        ->call('openEdit', $other->id)
        ->assertSet('editModalOpen', false);
});

test('portal can update allowed fields on owned registration', function () {
    $cong = seedPortalCongregation();
    $reg = ParkingRegistration::query()->create([
        'name' => 'User',
        'congregation' => $cong->name,
        'contact_number' => '111',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
        'email' => 'user@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $cong->uuid)
        ->set('password', 'Happiness')
        ->call('login')
        ->call('openEdit', $reg->id)
        ->set('vehicleReg', 'XY99 ZZZ')
        ->set('days', ['Saturday'])
        ->call('saveEdit')
        ->assertHasNoErrors();

    $reg->refresh();
    expect($reg->vehicle_registration)->toBe('XY99ZZZ')
        ->and($reg->days)->toBe(['Saturday'])
        ->and($reg->name)->toBe('User')
        ->and($reg->email)->toBe('user@example.test');
});

test('portal survey summary counts all cars when disabled spaces not requested on survey', function () {
    $cong = seedPortalCongregation('Broadstairs', 2);
    CongregationNumbersResponse::query()
        ->where('congregation_id', $cong->id)
        ->update(['disabled_parking_required' => false]);

    ParkingRegistration::query()->create([
        'name' => 'User',
        'congregation' => $cong->name,
        'contact_number' => '111',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
        'email' => 'broad@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => true,
    ]);

    $component = Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $cong->uuid)
        ->set('password', 'Happiness')
        ->call('login');

    expect($component->instance()->surveySummary['filled_cars'])->toBe(1)
        ->and($component->instance()->surveySummary['car_tickets'])->toBe(2);
});

test('portal can save edit with elderly infirm no selected via buttons', function () {
    $cong = seedPortalCongregation();
    $reg = ParkingRegistration::query()->create([
        'name' => 'User',
        'congregation' => $cong->name,
        'contact_number' => '111',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
        'email' => 'edit@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $cong->uuid)
        ->set('password', 'Happiness')
        ->call('login')
        ->call('openEdit', $reg->id)
        ->set('vehicleReg', 'CD34 EFG')
        ->set('elderlyInfirmParking', '0')
        ->set('days', ['Saturday'])
        ->call('saveEdit')
        ->assertHasNoErrors();

    $reg->refresh();
    expect($reg->vehicle_registration)->toBe('CD34EFG');
});

test('portal can cancel owned registration with cancelled_via portal', function () {
    $cong = seedPortalCongregation();
    $reg = ParkingRegistration::query()->create([
        'name' => 'User',
        'congregation' => $cong->name,
        'contact_number' => '111',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
        'email' => 'cancel@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $cong->uuid)
        ->set('password', 'Happiness')
        ->call('login')
        ->call('cancelRegistration', $reg->id)
        ->assertDontSee('AB12CDE');

    expect(ParkingRegistration::query()->find($reg->id))->toBeNull();
    $trashed = ParkingRegistration::onlyTrashed()->find($reg->id);
    expect($trashed)->not->toBeNull()
        ->and($trashed->cancelled_via)->toBe('portal')
        ->and($trashed->trashed())->toBeTrue();
});

test('portal cannot cancel another congregations registration', function () {
    $congA = seedPortalCongregation('Hall A');
    $congB = seedPortalCongregation('Hall B');

    $other = ParkingRegistration::query()->create([
        'name' => 'Other',
        'congregation' => $congB->name,
        'contact_number' => '111',
        'vehicle_registration' => 'ZZ99ZZZ',
        'days' => ['Friday'],
        'email' => 'other-cancel@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $congA->uuid)
        ->set('password', 'Happiness')
        ->call('login')
        ->call('cancelRegistration', $other->id);

    expect($other->fresh())->not->toBeNull()
        ->and($other->fresh()->trashed())->toBeFalse();
});

test('portal download ticket redirects with token for owned registration', function () {
    $cong = seedPortalCongregation();
    $reg = ParkingRegistration::query()->create([
        'name' => 'User',
        'congregation' => $cong->name,
        'contact_number' => '111',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
        'email' => 'dl@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $cong->uuid)
        ->set('password', 'Happiness')
        ->call('login')
        ->call('downloadTicket', $reg->id)
        ->assertRedirectContains('/congregation-portal/download-ticket/');
});

test('portal cannot download another congregations ticket', function () {
    $congA = seedPortalCongregation('Hall A');
    $congB = seedPortalCongregation('Hall B');

    $other = ParkingRegistration::query()->create([
        'name' => 'Other',
        'congregation' => $congB->name,
        'contact_number' => '111',
        'vehicle_registration' => 'ZZ99ZZZ',
        'days' => ['Friday'],
        'email' => 'other-dl@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    $component = Livewire::test(CongregationPortal::class)
        ->set('congregationCode', $congA->uuid)
        ->set('password', 'Happiness')
        ->call('login')
        ->call('downloadTicket', $other->id);

    // Ownership failure returns null (no redirect to the download stream).
    expect($component->effects['redirect'] ?? null)->toBeNull();
    expect($other->fresh())->not->toBeNull();
});
