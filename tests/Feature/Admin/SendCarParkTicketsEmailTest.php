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
        ->toContain('Here are 2 tickets')
        ->not->toContain('A4');

    expect(\App\Support\TicketEmailBody::renderHtml(1, 'Radisson Hotel Guest'))
        ->toContain('Radisson Hotel Guest')
        ->toContain('<strong style="font-size:17px;">Please print the attached car park ticket on A4 paper.</strong>')
        ->toContain('border:2px solid #111827');
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

test('ticket email body includes optional note', function () {
    Setting::set(\App\Support\TicketEmailBody::SETTING_KEY, "Hello {{congregation}}.\n\n{{count}} ticket(s).");

    $html = \App\Support\TicketEmailBody::renderHtml(1, 'Note Hall', "Please use gate B.\nThanks.");

    expect($html)
        ->toContain('Hello Note Hall')
        ->toContain('Note from the parking team:')
        ->toContain('Please use gate B.')
        ->toContain('Thanks.');
});

test('resend registration ticket uses registration email note and settings cc', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Resend Park',
        'capacity' => 50,
        'color' => '#2563eb',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Resend Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Bob Example',
        'congregation' => $congregation->name,
        'contact_number' => '07700999888',
        'email' => 'bob@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'BB22CCC',
        'days' => ['Saturday'],
    ]);

    Setting::set(TicketEmailCcList::SETTING_KEY, "nathan-simpson@outlook.com\nops@jwconv.uk");

    $this->mock(MasterPassPdfGenerator::class, function ($mock) use ($registration) {
        $mock->shouldReceive('generateForIds')
            ->once()
            ->with([$registration->id])
            ->andReturn([
                [
                    'filename' => 'Bob Example.pdf',
                    'content' => '%PDF-1.4 fake',
                    'registration' => $registration,
                ],
            ]);
    });

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('openResendTicketModal', $registration->id)
        ->assertSet('resendModalOpen', true)
        ->assertSet('resendEmailTo', 'bob@example.test')
        ->assertSet('resendNote', function (string $note): bool {
            return str_contains($note, 'Due to limitations in the stadium car parks')
                && str_contains($note, 'Resend Park')
                && str_contains($note, 'Parking Team Twickenham');
        })
        ->assertSee('Also CC’d (from Settings)')
        ->set('resendNote', 'Corrected vehicle registration as discussed.')
        ->call('resendTicket')
        ->assertHasNoErrors()
        ->assertSet('resendModalOpen', false)
        ->assertSet('ticketsSentSuccessOpen', true);

    Mail::assertSent(CarParkTicketsMail::class, function (CarParkTicketsMail $mail) {
        expect($mail->recipientEmail)->toBe('bob@example.test')
            ->and($mail->ticketCount)->toBe(1)
            ->and($mail->congregationLabel)->toBe('Resend Hall')
            ->and($mail->note)->toBe('Corrected vehicle registration as discussed.')
            ->and($mail->ccAddresses)->toBe([
                'nathan-simpson@outlook.com',
                'ops@jwconv.uk',
            ]);

        $html = $mail->content()->htmlString;
        expect($html)
            ->toContain('Corrected vehicle registration as discussed.')
            ->toContain('Note from the parking team:');

        return true;
    });

    expect($registration->fresh()->ticket_sent_at)->not->toBeNull();
});

test('resend registration ticket can use alternate email', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Alt Park',
        'capacity' => 20,
        'color' => '#dc2626',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Alt Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Carol Example',
        'congregation' => $congregation->name,
        'contact_number' => '07700123456',
        'email' => 'carol@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'CC33DDD',
        'days' => ['Sunday'],
    ]);

    $this->mock(MasterPassPdfGenerator::class, function ($mock) use ($registration) {
        $mock->shouldReceive('generateForIds')
            ->once()
            ->with([$registration->id])
            ->andReturn([
                [
                    'filename' => 'Carol Example.pdf',
                    'content' => '%PDF-1.4 fake',
                    'registration' => $registration,
                ],
            ]);
    });

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('openResendTicketModal', $registration->id)
        ->set('resendEmailTo', 'alternate@example.test')
        ->set('resendNote', '')
        ->call('resendTicket')
        ->assertHasNoErrors();

    Mail::assertSent(CarParkTicketsMail::class, function (CarParkTicketsMail $mail) {
        return $mail->recipientEmail === 'alternate@example.test' && $mail->note === null;
    });
});

test('resend note template fills registration car park and can be reset', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Rosebine 1',
        'capacity' => 40,
        'color' => '#16a34a',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Template Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Dana Example',
        'congregation' => $congregation->name,
        'contact_number' => '07700777777',
        'email' => 'dana@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'DD44EEE',
        'days' => ['Friday'],
        'car_park_id' => $park->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('openResendTicketModal', $registration->id)
        ->assertSet('resendNote', function (string $note): bool {
            return str_contains($note, 'free parking ticket in Rosebine 1.')
                && str_contains($note, 'Dear delegate');
        })
        ->set('resendNote', 'custom note')
        ->call('applyResendNoteTemplate')
        ->assertSet('resendNote', function (string $note): bool {
            return str_contains($note, 'Rosebine 1')
                && str_contains($note, 'Parking Team Twickenham');
        });
});

test('resend registration ticket requires print permission', function () {
    $viewer = User::factory()->attendant()->create();
    $viewer->givePermissionTo('registrations.view');

    $registration = ParkingRegistration::query()->create([
        'name' => 'No Print',
        'congregation' => 'Some Hall',
        'contact_number' => '07700000000',
        'email' => 'noprint@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'NP11PRT',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($viewer)
        ->test(Registrations::class)
        ->call('openResendTicketModal', $registration->id)
        ->assertForbidden();
});
