<?php

declare(strict_types=1);

use App\Livewire\Admin\HotelGuestParkingRequestDetail;
use App\Livewire\Public\RadissonGuestParking;
use App\Mail\CarParkTicketsMail;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\HotelGuestParkingRequest;
use App\Models\ParkingRegistration;
use App\Models\Setting;
use App\Models\User;
use App\Services\MasterPassPdfGenerator;
use App\Support\TicketEmailCcList;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function seedHotelGuestCarPark(string $name = 'North Car Park', array $overrides = []): CarPark
{
    return CarPark::query()->create(array_merge([
        'name' => $name,
        'capacity' => 100,
        'capacity_friday' => 100,
        'capacity_saturday' => 100,
        'capacity_sunday' => 100,
        'color' => '#2563eb',
    ], $overrides));
}

function seedPendingHotelGuestRequest(array $overrides = []): HotelGuestParkingRequest
{
    return HotelGuestParkingRequest::query()->create(array_merge([
        'name' => 'Jordan Guest',
        'contact_number' => '07700900999',
        'vehicle_registration' => 'HG12ABC',
        'email' => 'jordan@example.test',
        'days' => ['Wednesday', 'Friday', 'Sunday'],
        'status' => HotelGuestParkingRequest::STATUS_PENDING,
    ], $overrides));
}

test('radisson guest form creates a pending request', function () {
    Livewire::test(RadissonGuestParking::class)
        ->set('name', 'Jordan Guest')
        ->set('contactNumber', '07700900999')
        ->set('vehicleReg', 'HG12 ABC')
        ->set('email', 'jordan@example.test')
        ->set('days', ['Wednesday', 'Thursday', 'Friday'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $row = HotelGuestParkingRequest::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->status)->toBe(HotelGuestParkingRequest::STATUS_PENDING)
        ->and($row->name)->toBe('Jordan Guest')
        ->and($row->contact_number)->toBe('07700900999')
        ->and($row->vehicle_registration)->toBe('HG12ABC')
        ->and($row->email)->toBe('jordan@example.test')
        ->and($row->days)->toBe(['Wednesday', 'Thursday', 'Friday'])
        ->and($row->parking_registration_id)->toBeNull();

    expect(ParkingRegistration::query()->count())->toBe(0);
});

test('radisson guest form rejects jw organisation email domains', function () {
    Livewire::test(RadissonGuestParking::class)
        ->set('name', 'Jordan Guest')
        ->set('contactNumber', '07700900999')
        ->set('vehicleReg', 'HG12ABC')
        ->set('email', 'guest@jw.org')
        ->set('days', ['Friday'])
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(HotelGuestParkingRequest::query()->count())->toBe(0);

    Livewire::test(RadissonGuestParking::class)
        ->set('name', 'Jordan Guest')
        ->set('contactNumber', '07700900999')
        ->set('vehicleReg', 'HG12ABC')
        ->set('email', 'guest@mail.jwpub.org')
        ->set('days', ['Friday'])
        ->call('submit')
        ->assertHasErrors(['email']);

    Livewire::test(RadissonGuestParking::class)
        ->set('name', 'Jordan Guest')
        ->set('contactNumber', '07700900999')
        ->set('vehicleReg', 'HG12ABC')
        ->set('email', 'guest@bethel.jw.org')
        ->set('days', ['Friday'])
        ->call('submit')
        ->assertHasErrors(['email']);
});

test('radisson guest form requires at least one wed-sun day', function () {
    Livewire::test(RadissonGuestParking::class)
        ->set('name', 'Jordan Guest')
        ->set('contactNumber', '07700900999')
        ->set('vehicleReg', 'HG12ABC')
        ->set('email', 'jordan@example.test')
        ->set('days', [])
        ->call('submit')
        ->assertHasErrors(['days']);

    expect(HotelGuestParkingRequest::query()->count())->toBe(0);
});

test('approve creates radisson hotel guest registration emails ticket with ccs', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    seedHotelGuestCarPark('West Car Park');
    $north = seedHotelGuestCarPark('North Car Park');
    $request = seedPendingHotelGuestRequest();

    Setting::set(TicketEmailCcList::SETTING_KEY, "nathan-simpson@outlook.com\nops@example.com");

    $this->mock(MasterPassPdfGenerator::class, function ($mock): void {
        $mock->shouldReceive('generateForIds')
            ->once()
            ->andReturnUsing(function (array $ids): array {
                $registration = ParkingRegistration::query()->findOrFail($ids[0]);

                return [
                    [
                        'filename' => 'Jordan Guest.pdf',
                        'content' => '%PDF-fake',
                        'registration' => $registration,
                    ],
                ];
            });
    });

    Livewire::actingAs($admin)
        ->test(HotelGuestParkingRequestDetail::class, ['hotelGuestParkingRequest' => $request])
        ->call('openApproveModal')
        ->assertSet('approveCarParkId', (string) $north->id)
        ->set('approveCarParkId', (string) $north->id)
        ->call('approve')
        ->assertHasNoErrors();

    $request->refresh();
    expect($request->status)->toBe(HotelGuestParkingRequest::STATUS_APPROVED)
        ->and($request->car_park_id)->toBe($north->id)
        ->and($request->parking_registration_id)->not->toBeNull()
        ->and($request->actioned_by)->toBe($admin->id);

    $registration = ParkingRegistration::query()->findOrFail($request->parking_registration_id);
    expect($registration->congregation)->toBe(HotelGuestParkingRequest::CONGREGATION_NAME)
        ->and($registration->car_park_id)->toBe($north->id)
        ->and($registration->name)->toBe('Jordan Guest')
        ->and($registration->contact_number)->toBe('07700900999')
        ->and($registration->email)->toBe('jordan@example.test')
        ->and($registration->vehicle_registration)->toBe('HG12ABC')
        ->and($registration->days)->toBe(['Wednesday', 'Friday', 'Sunday'])
        ->and($registration->vehicle_type)->toBe('car')
        ->and($registration->is_circuit_overseer)->toBeFalse()
        ->and($registration->ticket_sent_at)->not->toBeNull();

    expect(Congregation::query()->where('name', HotelGuestParkingRequest::CONGREGATION_NAME)->exists())->toBeTrue();

    Mail::assertSent(CarParkTicketsMail::class, function (CarParkTicketsMail $mail): bool {
        return $mail->hasTo('jordan@example.test')
            && in_array('nathan-simpson@outlook.com', $mail->ccAddresses, true)
            && in_array('ops@example.com', $mail->ccAddresses, true);
    });
});

test('reject leaves no parking registration', function () {
    $admin = User::factory()->admin()->create();
    seedHotelGuestCarPark('North Car Park');
    $request = seedPendingHotelGuestRequest([
        'name' => 'Rejected Guest',
        'email' => 'rejected@example.test',
    ]);

    Livewire::actingAs($admin)
        ->test(HotelGuestParkingRequestDetail::class, ['hotelGuestParkingRequest' => $request])
        ->set('adminNotes', 'No hotel booking found.')
        ->call('reject')
        ->assertHasNoErrors();

    $request->refresh();
    expect($request->status)->toBe(HotelGuestParkingRequest::STATUS_REJECTED)
        ->and($request->parking_registration_id)->toBeNull()
        ->and($request->admin_notes)->toBe('No hotel booking found.')
        ->and($request->actioned_by)->toBe($admin->id);

    expect(ParkingRegistration::query()->count())->toBe(0);
});

test('approve modal defaults to north car park when present', function () {
    $admin = User::factory()->admin()->create();
    seedHotelGuestCarPark('South Car Park');
    $north = seedHotelGuestCarPark('North Overflow');
    $request = seedPendingHotelGuestRequest();

    Livewire::actingAs($admin)
        ->test(HotelGuestParkingRequestDetail::class, ['hotelGuestParkingRequest' => $request])
        ->call('openApproveModal')
        ->assertSet('approveCarParkId', (string) $north->id)
        ->assertSet('approveModalOpen', true);
});

test('approve modal falls back to first car park when no north park exists', function () {
    $admin = User::factory()->admin()->create();
    seedHotelGuestCarPark('East Car Park');
    seedHotelGuestCarPark('West Car Park');
    $request = seedPendingHotelGuestRequest();

    $expected = CarPark::query()->orderBy('name')->first();

    Livewire::actingAs($admin)
        ->test(HotelGuestParkingRequestDetail::class, ['hotelGuestParkingRequest' => $request])
        ->call('openApproveModal')
        ->assertSet('approveCarParkId', (string) $expected->id);
});
