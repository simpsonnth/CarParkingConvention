<?php

use App\Livewire\Admin\RegistrationsTrash;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

function seedTrashedRegistration(array $overrides = []): ParkingRegistration
{
    $reg = ParkingRegistration::query()->create(array_merge([
        'name' => 'Test User',
        'congregation' => 'Alpha',
        'contact_number' => '1',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
        'email' => 'trash@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
        'is_circuit_overseer' => false,
    ], $overrides));
    $reg->delete();

    return ParkingRegistration::onlyTrashed()->findOrFail($reg->id);
}

test('admin can permanently delete one trashed registration from recycle bin', function () {
    $reg = seedTrashedRegistration();

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(RegistrationsTrash::class)
        ->call('forceDelete', $reg->id);

    expect(ParkingRegistration::withTrashed()->find($reg->id))->toBeNull();
});

test('admin can empty recycle bin permanently', function () {
    seedTrashedRegistration(['email' => 'a@example.test', 'vehicle_registration' => 'AA11AAA']);
    seedTrashedRegistration(['email' => 'b@example.test', 'vehicle_registration' => 'BB22BBB']);

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(RegistrationsTrash::class)
        ->call('emptyTrashPermanently');

    expect(ParkingRegistration::withTrashed()->whereNotNull('deleted_at')->count())->toBe(0);
});
