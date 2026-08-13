<?php

declare(strict_types=1);

use App\Livewire\Admin\Registrations;
use App\Mail\RegistrationBroadcastMail;
use App\Models\CarPark;
use App\Models\HotelGuestParkingRequest;
use App\Models\OutboundEmail;
use App\Models\ParkingRegistration;
use App\Models\User;
use App\Services\OutboundEmailProcessor;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function makeBroadcastPark(string $name): CarPark
{
    return CarPark::query()->create([
        'name' => $name,
        'capacity' => 100,
        'location' => 'Test',
    ]);
}

function makeBroadcastRegistration(array $overrides = []): ParkingRegistration
{
    return ParkingRegistration::query()->create(array_merge([
        'name' => 'Guest Example',
        'congregation' => HotelGuestParkingRequest::CONGREGATION_NAME,
        'contact_number' => '07700900111',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
        'email' => 'guest@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
        'is_circuit_overseer' => false,
    ], $overrides));
}

test('admin can queue broadcast emails for filtered radisson west guests', function () {
    Mail::fake();

    $west = makeBroadcastPark('West');
    $north = makeBroadcastPark('North');

    $westA = makeBroadcastRegistration([
        'name' => 'West Alice',
        'email' => 'alice@example.test',
        'vehicle_registration' => 'WA12AAA',
        'car_park_id' => $west->id,
    ]);
    $westB = makeBroadcastRegistration([
        'name' => 'West Bob',
        'email' => 'bob@example.test',
        'vehicle_registration' => 'WB34BBB',
        'car_park_id' => $west->id,
    ]);
    // Duplicate email on another West ticket — should only get one outbound row.
    makeBroadcastRegistration([
        'name' => 'West Alice Duplicate',
        'email' => 'alice@example.test',
        'vehicle_registration' => 'WA56CCC',
        'car_park_id' => $west->id,
    ]);
    makeBroadcastRegistration([
        'name' => 'North Nora',
        'email' => 'nora@example.test',
        'vehicle_registration' => 'NN78DDD',
        'car_park_id' => $north->id,
    ]);
    makeBroadcastRegistration([
        'name' => 'Other Hall',
        'congregation' => 'Alpha Hall',
        'email' => 'other@example.test',
        'vehicle_registration' => 'OH90EEE',
        'car_park_id' => $west->id,
    ]);

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterCongregations', [HotelGuestParkingRequest::CONGREGATION_NAME])
        ->set('filterCarParks', [(string) $west->id])
        ->call('openBroadcastModal')
        ->assertSet('broadcastModalOpen', true)
        ->assertSet('broadcastScope', 'filters')
        ->set('broadcastSubject', 'West arrival note')
        ->set('broadcastBody', 'Hello {{name}}, please use Gate B.')
        ->call('sendRegistrationBroadcast')
        ->assertHasNoErrors()
        ->assertSet('broadcastModalOpen', false);

    $rows = OutboundEmail::query()
        ->where('type', OutboundEmail::TYPE_REGISTRATION_BROADCAST)
        ->orderBy('to_email')
        ->get();

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('to_email')->all())->toBe(['alice@example.test', 'bob@example.test']);

    expect($rows->firstWhere('to_email', 'alice@example.test')?->payload['name'] ?? null)->toBe('West Alice');

    Mail::assertSent(RegistrationBroadcastMail::class, 2);
    Mail::assertSent(RegistrationBroadcastMail::class, function (RegistrationBroadcastMail $mail) use ($westA): bool {
        return $mail->hasTo('alice@example.test')
            && $mail->emailSubject === 'West arrival note'
            && str_contains($mail->render(), 'West Alice')
            && str_contains($mail->render(), 'Gate B');
    });

    expect(OutboundEmail::query()->where('to_email', 'nora@example.test')->exists())->toBeFalse();
    expect(OutboundEmail::query()->where('to_email', 'other@example.test')->exists())->toBeFalse();
    expect($westB->email)->toBe('bob@example.test');
});

test('selected scope ignores other filtered registrations', function () {
    Mail::fake();

    $west = makeBroadcastPark('West Selected');
    $a = makeBroadcastRegistration([
        'name' => 'Selected Only',
        'email' => 'selected@example.test',
        'vehicle_registration' => 'SE12AAA',
        'car_park_id' => $west->id,
    ]);
    makeBroadcastRegistration([
        'name' => 'Not Selected',
        'email' => 'not-selected@example.test',
        'vehicle_registration' => 'NS34BBB',
        'car_park_id' => $west->id,
    ]);

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterCarParks', [(string) $west->id])
        ->set('selectedIds', [(string) $a->id])
        ->call('openBroadcastModal')
        ->assertSet('broadcastScope', 'selected')
        ->set('broadcastSubject', 'Selected note')
        ->set('broadcastBody', 'Only you.')
        ->call('sendRegistrationBroadcast')
        ->assertHasNoErrors();

    expect(OutboundEmail::query()->where('type', OutboundEmail::TYPE_REGISTRATION_BROADCAST)->count())->toBe(1)
        ->and(OutboundEmail::query()->where('to_email', 'selected@example.test')->exists())->toBeTrue()
        ->and(OutboundEmail::query()->where('to_email', 'not-selected@example.test')->exists())->toBeFalse();
});

test('blank emails are skipped and name merge tag is substituted', function () {
    Mail::fake();

    $west = makeBroadcastPark('West Blank');
    makeBroadcastRegistration([
        'name' => 'Has Email',
        'email' => 'has-email@example.test',
        'vehicle_registration' => 'HE12AAA',
        'car_park_id' => $west->id,
    ]);
    makeBroadcastRegistration([
        'name' => 'Blank Email',
        'email' => '',
        'vehicle_registration' => 'BE56CCC',
        'car_park_id' => $west->id,
    ]);
    makeBroadcastRegistration([
        'name' => 'Invalid Email',
        'email' => 'not-an-email',
        'vehicle_registration' => 'IE78DDD',
        'car_park_id' => $west->id,
    ]);

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterCarParks', [(string) $west->id])
        ->call('openBroadcastModal')
        ->set('broadcastScope', 'filters')
        ->set('broadcastSubject', 'Hello subject')
        ->set('broadcastBody', 'Dear {{name}}, welcome.')
        ->call('sendRegistrationBroadcast')
        ->assertHasNoErrors();

    expect(OutboundEmail::query()->where('type', OutboundEmail::TYPE_REGISTRATION_BROADCAST)->count())->toBe(1);

    Mail::assertSent(RegistrationBroadcastMail::class, function (RegistrationBroadcastMail $mail): bool {
        $html = $mail->render();

        return $mail->hasTo('has-email@example.test')
            && str_contains($html, 'Dear Has Email, welcome.')
            && ! str_contains($html, '{{name}}');
    });
});

test('user without registrations.manage cannot send broadcast', function () {
    Permission::findOrCreate('registrations.view');
    $user = User::factory()->create();
    $user->givePermissionTo('registrations.view');

    Livewire::actingAs($user)
        ->test(Registrations::class)
        ->call('openBroadcastModal')
        ->assertForbidden();
});

test('processor dispatches registration broadcast type', function () {
    Mail::fake();

    $outbound = OutboundEmail::query()->create([
        'type' => OutboundEmail::TYPE_REGISTRATION_BROADCAST,
        'status' => OutboundEmail::STATUS_PENDING,
        'to_email' => 'dispatch@example.test',
        'payload' => [
            'subject' => 'Processor subject',
            'body' => 'Hi {{name}}',
            'name' => 'Dispatch User',
            'registration_id' => 1,
            'broadcast_batch_id' => 'test-batch',
        ],
        'attempts' => 0,
    ]);

    $result = app(OutboundEmailProcessor::class)->process($outbound);

    expect($result)->toBe('sent')
        ->and($outbound->fresh()->status)->toBe(OutboundEmail::STATUS_SENT);

    Mail::assertSent(RegistrationBroadcastMail::class, function (RegistrationBroadcastMail $mail): bool {
        return $mail->hasTo('dispatch@example.test')
            && str_contains($mail->render(), 'Hi Dispatch User');
    });
});
