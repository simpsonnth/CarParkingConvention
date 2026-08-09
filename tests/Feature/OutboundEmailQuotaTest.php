<?php

declare(strict_types=1);

use App\Actions\Registrations\SendCarParkTicketsEmail;
use App\Livewire\Admin\HotelGuestParkingRequestDetail;
use App\Mail\CarParkTicketsMail;
use App\Models\CarPark;
use App\Models\HotelGuestParkingRequest;
use App\Models\OutboundEmail;
use App\Models\ParkingRegistration;
use App\Models\User;
use App\Services\MasterPassPdfGenerator;
use App\Services\OutboundEmailProcessor;
use App\Support\MailSendingQuota;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    Cache::forget(MailSendingQuota::CACHE_KEY);
});

function seedOutboundTicketRegistration(): ParkingRegistration
{
    $park = CarPark::query()->create([
        'name' => 'Quota Park',
        'capacity' => 50,
        'capacity_friday' => 50,
        'capacity_saturday' => 50,
        'capacity_sunday' => 50,
        'color' => '#111827',
    ]);

    return ParkingRegistration::query()->create([
        'name' => 'Quota Guest',
        'congregation' => HotelGuestParkingRequest::CONGREGATION_NAME,
        'car_park_id' => $park->id,
        'vehicle_type' => 'car',
        'vehicle_registration' => 'QU12OTA',
        'contact_number' => '07700900999',
        'email' => 'quota@example.test',
        'days' => ['Friday', 'Saturday', 'Sunday'],
        'elderly_infirm_parking' => false,
        'sharing_with_other_congregations' => false,
        'coach_captain_to_be_assigned' => false,
        'is_circuit_overseer' => false,
    ]);
}

test('outbound processor queues car park tickets when provider quota is exceeded', function () {
    Mail::fake();

    $registration = seedOutboundTicketRegistration();

    $this->mock(SendCarParkTicketsEmail::class, function ($mock) use ($registration): void {
        $mock->shouldReceive('execute')
            ->once()
            ->with([$registration->id], 'quota@example.test', null)
            ->andThrow(new RuntimeException('Expected response code "250" but got code "550", with message "550 You have reached your daily email sending quota.".'));
    });

    $result = app(OutboundEmailProcessor::class)->sendCarParkTicketsNowOrQueue(
        [$registration->id],
        'quota@example.test',
    );

    expect($result['status'])->toBe('queued')
        ->and(MailSendingQuota::isBlocked())->toBeTrue()
        ->and(OutboundEmail::query()->where('status', OutboundEmail::STATUS_PENDING)->count())->toBe(1);

    $pending = OutboundEmail::query()->first();
    expect($pending?->available_at)->not->toBeNull()
        ->and($pending?->available_at?->isFuture())->toBeTrue();
});

test('process due sends queued emails after quota clears', function () {
    Mail::fake();

    $registration = seedOutboundTicketRegistration();

    $email = OutboundEmail::query()->create([
        'type' => OutboundEmail::TYPE_CAR_PARK_TICKETS,
        'status' => OutboundEmail::STATUS_PENDING,
        'to_email' => 'quota@example.test',
        'payload' => [
            'registration_ids' => [$registration->id],
            'note' => null,
        ],
        'available_at' => now()->subMinute(),
        'attempts' => 1,
        'last_error' => 'quota',
    ]);

    $this->mock(MasterPassPdfGenerator::class, function ($mock) use ($registration): void {
        $mock->shouldReceive('generateForIds')
            ->once()
            ->andReturn([
                [
                    'filename' => 'Quota Guest.pdf',
                    'content' => '%PDF-fake',
                    'registration' => $registration,
                ],
            ]);
    });

    $processed = app(OutboundEmailProcessor::class)->processDue(10);

    expect($processed)->toBe(1);
    $email->refresh();
    expect($email->status)->toBe(OutboundEmail::STATUS_SENT)
        ->and($email->sent_at)->not->toBeNull();

    Mail::assertSent(CarParkTicketsMail::class);
});

test('approve still emails when quota is available and records outbound email as sent', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $park = CarPark::query()->create([
        'name' => 'North Car Park',
        'capacity' => 100,
        'capacity_friday' => 100,
        'capacity_saturday' => 100,
        'capacity_sunday' => 100,
        'color' => '#2563eb',
    ]);
    $request = HotelGuestParkingRequest::query()->create([
        'name' => 'Jordan Guest',
        'contact_number' => '07700900999',
        'vehicle_registration' => 'HG12ABC',
        'email' => 'jordan@example.test',
        'days' => ['Friday', 'Saturday'],
        'status' => HotelGuestParkingRequest::STATUS_PENDING,
    ]);

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
        ->set('approveCarParkId', (string) $park->id)
        ->call('approve')
        ->assertHasNoErrors();

    Mail::assertSent(CarParkTicketsMail::class);
    expect(OutboundEmail::query()->where('status', OutboundEmail::STATUS_SENT)->count())->toBe(1);
});
