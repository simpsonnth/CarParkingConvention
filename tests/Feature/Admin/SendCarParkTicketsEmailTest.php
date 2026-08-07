<?php

declare(strict_types=1);

use App\Actions\Registrations\SendCarParkTicketsEmail;
use App\Livewire\Admin\Registrations;
use App\Livewire\Admin\Settings;
use App\Mail\CarParkTicketsMail;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Models\Setting;
use App\Models\User;
use App\Services\MasterPassPdfGenerator;
use App\Support\TicketEmailCcList;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('ticket email cc list defaults to nathan simpson outlook', function () {
    expect(TicketEmailCcList::all())->toBe(['nathan-simpson@outlook.com']);
});

test('ticket email cc list parses and deduplicates addresses', function () {
    Setting::set(TicketEmailCcList::SETTING_KEY, "nathan-simpson@outlook.com\nother@example.com, nathan-simpson@outlook.com");

    expect(TicketEmailCcList::all())->toBe([
        'nathan-simpson@outlook.com',
        'other@example.com',
    ]);
});

test('settings page saves ticket email ccs', function () {
    $admin = User::factory()->admin()->create();

    \App\Services\CongregationPortalAuth::setPassword('secret-portal');

    Livewire::actingAs($admin)
        ->test(Settings::class)
        ->set('ticketEmailCcs', "nathan-simpson@outlook.com\nops@jwconv.uk")
        ->call('save')
        ->assertHasNoErrors();

    expect(TicketEmailCcList::all())->toBe([
        'nathan-simpson@outlook.com',
        'ops@jwconv.uk',
    ]);
});

test('unique person pdf filenames keep full name and disambiguate collisions', function () {
    $generator = app(MasterPassPdfGenerator::class);
    $used = [];

    $first = new ParkingRegistration(['name' => 'Jane Doe']);
    $first->id = 1;
    $second = new ParkingRegistration(['name' => 'Jane Doe']);
    $second->id = 2;

    expect($generator->uniquePersonFilename($first, $used))->toBe('Jane Doe.pdf');
    expect($generator->uniquePersonFilename($second, $used))->toBe('Jane Doe (2).pdf');
});

test('send car park tickets requires print permission', function () {
    $viewer = User::factory()->attendant()->create();
    $viewer->givePermissionTo('registrations.view');

    Livewire::actingAs($viewer)
        ->test(Registrations::class)
        ->set('selectedIds', [1])
        ->call('openSendTicketsModal')
        ->assertForbidden();
});

test('settings page saves ticket email body template', function () {
    $admin = User::factory()->admin()->create();
    \App\Services\CongregationPortalAuth::setPassword('secret-portal');

    Livewire::actingAs($admin)
        ->test(Settings::class)
        ->set('ticketEmailBody', "Hello {{congregation}},\n\nHere are {{count}} tickets.")
        ->call('save')
        ->assertHasNoErrors();

    expect(\App\Support\TicketEmailBody::renderHtml(2, 'Alpha Hall'))
        ->toContain('Hello Alpha Hall')
        ->toContain('Here are 2 tickets');
});

test('send car park tickets shows success popup after sending', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'North Park',
        'capacity' => 50,
        'color' => '#2563eb',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Email Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Alice Example',
        'congregation' => $congregation->name,
        'contact_number' => '07700111222',
        'email' => 'alice@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'AA11BBB',
        'days' => ['Friday'],
    ]);

    Setting::set(TicketEmailCcList::SETTING_KEY, "nathan-simpson@outlook.com\nextra-cc@example.com");

    $this->mock(MasterPassPdfGenerator::class, function ($mock) use ($registration) {
        $mock->shouldReceive('generateForIds')
            ->once()
            ->with([$registration->id])
            ->andReturn([
                [
                    'filename' => 'Alice Example.pdf',
                    'content' => '%PDF-1.4 fake',
                    'registration' => $registration,
                ],
            ]);
    });

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('selectedIds', [(string) $registration->id])
        ->call('openSendTicketsModal')
        ->assertSet('sendTicketsModalOpen', true)
        ->set('ticketEmailTo', 'congregation@example.com')
        ->call('sendCarParkTickets')
        ->assertHasNoErrors()
        ->assertSet('sendTicketsModalOpen', false)
        ->assertSet('selectedIds', [])
        ->assertSet('ticketsSentSuccessOpen', true)
        ->assertSee('Sent successfully');

    Mail::assertSent(CarParkTicketsMail::class, function (CarParkTicketsMail $mail) {
        expect($mail->recipientEmail)->toBe('congregation@example.com')
            ->and($mail->ticketCount)->toBe(1)
            ->and($mail->congregationLabel)->toBe('Email Hall')
            ->and($mail->ccAddresses)->toBe([
                'nathan-simpson@outlook.com',
                'extra-cc@example.com',
            ])
            ->and($mail->pdfAttachments[0]['filename'])->toBe('Alice Example.pdf');

        $envelope = $mail->envelope();
        expect($envelope->cc)->toHaveCount(2);

        return true;
    });

    expect($registration->fresh()->ticket_sent_at)->not->toBeNull();
});

test('send car park tickets action rejects invalid recipient', function () {
    $action = app(SendCarParkTicketsEmail::class);

    $action->execute([1], 'not-an-email');
})->throws(\Illuminate\Validation\ValidationException::class);

test('send car park tickets validates email in livewire modal', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('selectedIds', [1])
        ->set('sendTicketsModalOpen', true)
        ->set('ticketEmailTo', 'bad')
        ->call('sendCarParkTickets')
        ->assertHasErrors(['ticketEmailTo']);
});
