<?php

declare(strict_types=1);

use App\Livewire\Admin\TicketChangeRequestDetail;
use App\Livewire\Admin\TicketChangeRequests;
use App\Livewire\Public\TicketChangeRequest;
use App\Mail\CarParkTicketsMail;
use App\Mail\TicketCancellationMail;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Models\Setting;
use App\Models\TicketChangeRequest as TicketChangeRequestModel;
use App\Models\User;
use App\Services\MasterPassPdfGenerator;
use App\Support\TicketEmailCcList;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function seedTicketChangeCongregation(string $name = 'Change Req Hall'): Congregation
{
    return Congregation::query()->create([
        'name' => $name,
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);
}

function seedTicketChangeRegistration(Congregation $cong, array $overrides = []): ParkingRegistration
{
    return ParkingRegistration::query()->create(array_merge([
        'name' => 'Alex Driver',
        'congregation' => $cong->name,
        'contact_number' => '07700900111',
        'email' => 'alex@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday', 'Saturday'],
    ], $overrides));
}

test('email request logs free-text notes and allows organisation sender emails', function () {
    $cong = seedTicketChangeCongregation('Email Intake Hall');

    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'email_request')
        ->set('congregationCode', $cong->uuid)
        ->set('notes', "Dear Nathan,\n\nPlease send the coach ticket separately.\n\nKind regards,\nChristopher Herbert")
        ->set('notificationEmail', '74HerbertC@jwpub.org')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSet('submittedAutoApplied', false);

    $row = TicketChangeRequestModel::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->request_type)->toBe(TicketChangeRequestModel::TYPE_EMAIL_REQUEST)
        ->and($row->status)->toBe(TicketChangeRequestModel::STATUS_PENDING)
        ->and($row->notification_email)->toBe('74herbertc@jwpub.org')
        ->and($row->congregation)->toBe($cong->name)
        ->and($row->notes)->toContain('coach ticket')
        ->and($row->parking_registration_id)->toBeNull()
        ->and($row->requiresApproval())->toBeFalse();
});

test('ticket change request requires structured fields', function () {
    Livewire::test(TicketChangeRequest::class)
        ->call('submit')
        ->assertHasErrors(['requestType', 'congregationCode', 'notificationEmail', 'notificationEmailConfirmation']);

    expect(TicketChangeRequestModel::query()->count())->toBe(0);
});

test('ticket change request rejects mismatched notification email confirmation', function () {
    $cong = seedTicketChangeCongregation();
    $registration = seedTicketChangeRegistration($cong);

    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'cancellation')
        ->set('congregationCode', $cong->uuid)
        ->set('parkingRegistrationId', (string) $registration->id)
        ->set('confirmOwnership', 'AB12CDE')
        ->set('notificationEmail', 'person@example.test')
        ->set('notificationEmailConfirmation', 'other@example.test')
        ->call('submit')
        ->assertHasErrors(['notificationEmailConfirmation']);

    expect(TicketChangeRequestModel::query()->count())->toBe(0);
});

test('ticket change request rejects unknown congregation', function () {
    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'cancellation')
        ->set('congregationCode', 'not-a-real-congregation-code')
        ->set('parkingRegistrationId', '1')
        ->set('confirmOwnership', 'AB12CDE')
        ->set('notificationEmail', 'person@example.test')
        ->set('notificationEmailConfirmation', 'person@example.test')
        ->call('submit')
        ->assertHasErrors(['congregationCode']);

    expect(TicketChangeRequestModel::query()->count())->toBe(0);
});

test('ticket change request rejects organisation notification emails', function () {
    $cong = seedTicketChangeCongregation();
    $registration = seedTicketChangeRegistration($cong);

    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'cancellation')
        ->set('congregationCode', $cong->uuid)
        ->set('parkingRegistrationId', (string) $registration->id)
        ->set('confirmOwnership', 'AB12CDE')
        ->set('notificationEmail', 'secretary@jwpub.org')
        ->set('notificationEmailConfirmation', 'secretary@jwpub.org')
        ->call('submit')
        ->assertHasErrors(['notificationEmail']);

    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'cancellation')
        ->set('congregationCode', $cong->uuid)
        ->set('parkingRegistrationId', (string) $registration->id)
        ->set('confirmOwnership', 'AB12CDE')
        ->set('notificationEmail', 'someone@mail.jw.org')
        ->set('notificationEmailConfirmation', 'someone@mail.jw.org')
        ->call('submit')
        ->assertHasErrors(['notificationEmail']);

    expect(TicketChangeRequestModel::query()->count())->toBe(0);
});

test('ticket change request rejects vehicle registration mismatch', function () {
    $cong = seedTicketChangeCongregation();
    $registration = seedTicketChangeRegistration($cong);

    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'cancellation')
        ->set('congregationCode', $cong->uuid)
        ->set('parkingRegistrationId', (string) $registration->id)
        ->set('confirmOwnership', 'WRONGREG')
        ->set('notificationEmail', 'person@example.test')
        ->set('notificationEmailConfirmation', 'person@example.test')
        ->call('submit')
        ->assertHasErrors(['confirmOwnership']);

    expect(TicketChangeRequestModel::query()->count())->toBe(0);
});

test('field update applies changes emails ticket and marks completed as auto', function () {
    Mail::fake();

    $cong = seedTicketChangeCongregation();
    $registration = seedTicketChangeRegistration($cong);

    Setting::set(TicketEmailCcList::SETTING_KEY, "nathan-simpson@outlook.com\nops@example.com");

    $this->mock(MasterPassPdfGenerator::class, function ($mock) use ($registration): void {
        $mock->shouldReceive('generateForIds')
            ->once()
            ->with([$registration->id])
            ->andReturn([
                [
                    'filename' => 'Alex Driver.pdf',
                    'content' => '%PDF-fake',
                    'registration' => $registration,
                ],
            ]);
    });

    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'field_update')
        ->set('congregationCode', $cong->uuid)
        ->set('parkingRegistrationId', (string) $registration->id)
        ->set('confirmOwnership', 'AB12 CDE')
        ->set('changeVehicleRegistration', true)
        ->set('newVehicleRegistration', 'XY99ZZZ')
        ->set('changeName', true)
        ->set('newName', 'Alex Updated')
        ->set('notificationEmail', 'person@example.test')
        ->set('notificationEmailConfirmation', 'person@example.test')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSet('submittedAutoApplied', true);

    $registration->refresh();
    expect($registration->vehicle_registration)->toBe('XY99ZZZ')
        ->and($registration->name)->toBe('Alex Updated');

    $row = TicketChangeRequestModel::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->request_type)->toBe(TicketChangeRequestModel::TYPE_FIELD_UPDATE)
        ->and($row->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($row->actioned_by)->toBeNull()
        ->and($row->wasAutoCompleted())->toBeTrue();

    Mail::assertSent(CarParkTicketsMail::class, function (CarParkTicketsMail $mail): bool {
        return $mail->hasTo('person@example.test')
            && in_array('nathan-simpson@outlook.com', $mail->ccAddresses, true)
            && in_array('ops@example.com', $mail->ccAddresses, true);
    });
});

test('cancellation stays pending until admin approves then soft deletes and emails', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation();
    $registration = seedTicketChangeRegistration($cong);

    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'cancellation')
        ->set('congregationCode', $cong->uuid)
        ->set('parkingRegistrationId', (string) $registration->id)
        ->set('confirmOwnership', 'AB12CDE')
        ->set('notificationEmail', 'person@example.test')
        ->set('notificationEmailConfirmation', 'person@example.test')
        ->set('notes', 'Please cancel this ticket.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSet('submittedAutoApplied', false);

    $row = TicketChangeRequestModel::query()->first();
    expect($row->status)->toBe(TicketChangeRequestModel::STATUS_PENDING)
        ->and($registration->fresh())->not->toBeNull();

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $row])
        ->call('openApproveModal')
        ->call('approve')
        ->assertHasNoErrors();

    expect($row->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($row->fresh()->actioned_by)->toBe($admin->id)
        ->and(ParkingRegistration::query()->find($registration->id))->toBeNull()
        ->and(ParkingRegistration::withTrashed()->find($registration->id)?->cancelled_via)->toBe('change_request');

    Mail::assertSent(TicketCancellationMail::class, function (TicketCancellationMail $mail) use ($registration): bool {
        return $mail->hasTo('person@example.test')
            && $mail->ticketNumber === $registration->ticketNumber();
    });
});

test('car park change and addition require car park on approve', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $park = CarPark::query()->create([
        'name' => 'South Park',
        'capacity' => 40,
        'color' => '#16a34a',
    ]);
    $cong = seedTicketChangeCongregation('Approve Park Hall');
    $registration = seedTicketChangeRegistration($cong, ['name' => 'Park Mover']);

    $this->mock(MasterPassPdfGenerator::class, function ($mock) use ($registration): void {
        $mock->shouldReceive('generateForIds')->andReturn([
            [
                'filename' => 'Park Mover.pdf',
                'content' => '%PDF-fake',
                'registration' => $registration,
            ],
        ]);
    });

    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'car_park_change')
        ->set('congregationCode', $cong->uuid)
        ->set('parkingRegistrationId', (string) $registration->id)
        ->set('confirmOwnership', 'AB12CDE')
        ->set('notes', 'Need closer parking for mobility reasons.')
        ->set('notificationEmail', 'mover@example.test')
        ->set('notificationEmailConfirmation', 'mover@example.test')
        ->call('submit')
        ->assertHasNoErrors();

    $carParkRequest = TicketChangeRequestModel::query()->where('request_type', 'car_park_change')->first();

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $carParkRequest])
        ->call('openApproveModal')
        ->call('approve')
        ->assertHasErrors(['approveCarParkId']);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $carParkRequest])
        ->call('openApproveModal')
        ->set('approveCarParkId', $park->id)
        ->call('approve')
        ->assertHasNoErrors();

    expect($registration->fresh()->car_park_id)->toBe($park->id)
        ->and($carParkRequest->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED);

    Livewire::test(TicketChangeRequest::class)
        ->set('requestType', 'addition')
        ->set('congregationCode', $cong->uuid)
        ->set('additionName', 'New Person')
        ->set('additionContactNumber', '07700900999')
        ->set('additionEmail', 'newperson@example.test')
        ->set('additionVehicleType', 'car')
        ->set('additionVehicleRegistration', 'NE11WWW')
        ->set('additionDays', ['Sunday'])
        ->set('notificationEmail', 'newperson@example.test')
        ->set('notificationEmailConfirmation', 'newperson@example.test')
        ->call('submit')
        ->assertHasNoErrors();

    $addition = TicketChangeRequestModel::query()->where('request_type', 'addition')->first();

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $addition])
        ->call('openApproveModal')
        ->set('approveCarParkId', $park->id)
        ->call('approve')
        ->assertHasNoErrors();

    $created = ParkingRegistration::query()->where('name', 'New Person')->first();
    expect($created)->not->toBeNull()
        ->and($created->car_park_id)->toBe($park->id)
        ->and($addition->fresh()->parking_registration_id)->toBe($created->id)
        ->and($addition->fresh()->actioned_by)->toBe($admin->id);
});

test('admin list shows completed by name or auto', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Completer']);
    $cong = seedTicketChangeCongregation('Completer Hall');

    TicketChangeRequestModel::query()->create([
        'name' => 'Manual Done',
        'congregation' => $cong->name,
        'notes' => 'Handled manually.',
        'status' => TicketChangeRequestModel::STATUS_COMPLETED,
        'actioned_at' => now(),
        'actioned_by' => $admin->id,
    ]);

    TicketChangeRequestModel::query()->create([
        'request_type' => TicketChangeRequestModel::TYPE_FIELD_UPDATE,
        'name' => 'Auto Done',
        'congregation' => $cong->name,
        'notes' => 'Auto field update.',
        'notification_email' => 'auto@example.test',
        'status' => TicketChangeRequestModel::STATUS_COMPLETED,
        'actioned_at' => now(),
        'actioned_by' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequests::class)
        ->call('setStatusFilter', 'completed')
        ->assertSee('Admin Completer')
        ->assertSee('Auto');
});

test('admin registrations list shows recently changed badge after field update', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Recent Change Hall');
    $registration = seedTicketChangeRegistration($cong, [
        'name' => 'Recent Change Person',
        'vehicle_registration' => 'RC11AAA',
    ]);

    TicketChangeRequestModel::query()->create([
        'request_type' => TicketChangeRequestModel::TYPE_FIELD_UPDATE,
        'parking_registration_id' => $registration->id,
        'name' => $registration->name,
        'congregation' => $cong->name,
        'notification_email' => 'recent@example.test',
        'notes' => 'Field update',
        'payload' => ['changes' => ['vehicle_registration' => 'RC22BBB']],
        'status' => TicketChangeRequestModel::STATUS_COMPLETED,
        'actioned_at' => now(),
        'actioned_by' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Admin\Registrations::class)
        ->set('search', 'Recent Change Person')
        ->assertSee('Recently changed');
});

test('pending car park change can be closed without applying when registration already cancelled', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Conflict Hall');
    $registration = seedTicketChangeRegistration($cong, [
        'name' => 'Conflict Person',
        'vehicle_registration' => 'CF11AAA',
    ]);

    $carParkChange = TicketChangeRequestModel::query()->create([
        'request_type' => TicketChangeRequestModel::TYPE_CAR_PARK_CHANGE,
        'parking_registration_id' => $registration->id,
        'name' => $registration->name,
        'congregation' => $cong->name,
        'notification_email' => 'conflict@example.test',
        'notes' => 'Please move car parks for mobility reasons.',
        'payload' => ['ticket_number' => $registration->ticketNumber()],
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    $registration->update(['cancelled_via' => 'change_request']);
    $registration->delete();

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $carParkChange])
        ->assertSee(__('management.ticket_change_requests.cannot_approve_missing_registration'))
        ->assertSee(__('management.ticket_change_requests.close_without_applying'))
        ->call('closeWithoutApplying')
        ->assertHasNoErrors();

    expect($carParkChange->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($carParkChange->fresh()->actioned_by)->toBe($admin->id)
        ->and($carParkChange->fresh()->admin_notes)->not->toBeNull();
});

test('admin can view ticket change requests list', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Admin View Hall');

    TicketChangeRequestModel::query()->create([
        'name' => 'Sam Parker',
        'congregation' => $cong->name,
        'notes' => 'Cancel ticket for vehicle AB12CDE please.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequests::class)
        ->assertSee('Sam Parker')
        ->assertSee('Admin View Hall')
        ->assertSee('Cancel ticket for vehicle AB12CDE please.');
});

test('admin can open ticket change request detail page', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Detail Hall');

    $row = TicketChangeRequestModel::query()->create([
        'name' => 'Sam Parker',
        'congregation' => $cong->name,
        'notes' => 'Cancel ticket for vehicle AB12CDE please.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $row])
        ->assertSee('Sam Parker')
        ->assertSee('Cancel ticket for vehicle AB12CDE please.');
});

test('admin can mark ticket change request completed and it moves to completed list', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Complete Hall');

    $pending = TicketChangeRequestModel::query()->create([
        'name' => 'Pending Person',
        'congregation' => $cong->name,
        'notes' => 'Please update registration to NEWREG1.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    $done = TicketChangeRequestModel::query()->create([
        'name' => 'Already Done',
        'congregation' => $cong->name,
        'notes' => 'Already handled cancellation.',
        'status' => TicketChangeRequestModel::STATUS_COMPLETED,
        'actioned_at' => now(),
        'actioned_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequests::class)
        ->assertSet('statusFilter', 'pending')
        ->assertSee('Pending Person')
        ->assertDontSee('Already Done')
        ->call('markCompleted', $pending->id)
        ->assertDontSee('Pending Person')
        ->call('setStatusFilter', 'completed')
        ->assertSee('Pending Person')
        ->assertSee('Already Done');

    $pending->refresh();
    expect($pending->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($pending->actioned_at)->not->toBeNull()
        ->and($pending->actioned_by)->toBe($admin->id)
        ->and($done->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED);
});

test('admin can reopen a completed ticket change request', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Reopen Hall');

    $row = TicketChangeRequestModel::query()->create([
        'name' => 'Reopen Me',
        'congregation' => $cong->name,
        'notes' => 'Needs another look at the vehicle reg.',
        'status' => TicketChangeRequestModel::STATUS_COMPLETED,
        'actioned_at' => now(),
        'actioned_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequests::class)
        ->call('setStatusFilter', 'completed')
        ->assertSee('Reopen Me')
        ->call('markPending', $row->id)
        ->call('setStatusFilter', 'pending')
        ->assertSee('Reopen Me');

    $row->refresh();
    expect($row->status)->toBe(TicketChangeRequestModel::STATUS_PENDING)
        ->and($row->actioned_at)->toBeNull()
        ->and($row->actioned_by)->toBeNull();
});

test('admin can save optional staff note on detail page', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Notes Hall');

    $row = TicketChangeRequestModel::query()->create([
        'name' => 'Note Person',
        'congregation' => $cong->name,
        'notes' => 'Please cancel ticket AB12CDE.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $row])
        ->set('adminNotes', 'Emailed confirmation on 5 Aug that the ticket was cancelled.')
        ->call('saveAdminNotes')
        ->assertHasNoErrors();

    expect($row->fresh()->admin_notes)->toBe('Emailed confirmation on 5 Aug that the ticket was cancelled.');
});

test('admin can mark completed while saving staff note from the detail page', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Complete Notes Hall');

    $row = TicketChangeRequestModel::query()->create([
        'name' => 'Complete With Note',
        'congregation' => $cong->name,
        'notes' => 'Change reg to NEWREG9.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $row])
        ->set('adminNotes', 'Updated registration and replied by email.')
        ->call('markCompleted')
        ->assertHasNoErrors();

    $row->refresh();
    expect($row->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($row->admin_notes)->toBe('Updated registration and replied by email.');
});

test('admin can create a ticket change request from the admin page', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Admin Create Hall');

    Livewire::actingAs($admin)
        ->test(TicketChangeRequests::class)
        ->call('openCreate')
        ->assertSet('createModalOpen', true)
        ->set('createName', 'Phone Caller')
        ->set('createCongregation', $cong->name)
        ->set('createNotes', 'Please cancel car park ticket for AB12CDE.')
        ->set('createAdminNotes', 'Taken by phone on reception desk.')
        ->call('saveCreate')
        ->assertHasNoErrors()
        ->assertSet('createModalOpen', false)
        ->assertSet('statusFilter', 'pending')
        ->assertSee('Phone Caller');

    $row = TicketChangeRequestModel::query()->where('name', 'Phone Caller')->first();
    expect($row)->not->toBeNull()
        ->and($row->congregation)->toBe($cong->name)
        ->and($row->status)->toBe(TicketChangeRequestModel::STATUS_PENDING)
        ->and($row->admin_notes)->toBe('Taken by phone on reception desk.');
});

test('admin can see related requests on detail page and mark all pending completed', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Related Hall');

    $first = TicketChangeRequestModel::query()->create([
        'name' => 'Jordan Lee',
        'congregation' => $cong->name,
        'notes' => 'Please cancel ticket for AB12CDE.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);
    $second = TicketChangeRequestModel::query()->create([
        'name' => 'jordan lee',
        'congregation' => $cong->name,
        'notes' => 'Also change another vehicle to XY99ZZZ.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);
    TicketChangeRequestModel::query()->create([
        'name' => 'Someone Else',
        'congregation' => $cong->name,
        'notes' => 'Unrelated request that should stay pending.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $first])
        ->assertSee('Also change another vehicle to XY99ZZZ.')
        ->set('adminNotes', 'Replied to both points in one email.')
        ->call('markAllRelatedPendingCompleted')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.ticket-change-requests'));

    expect($first->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($second->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($first->fresh()->admin_notes)->toBe('Replied to both points in one email.')
        ->and(TicketChangeRequestModel::query()->where('name', 'Someone Else')->value('status'))
        ->toBe(TicketChangeRequestModel::STATUS_PENDING);
});

test('admin list groups requests by congregation', function () {
    $admin = User::factory()->admin()->create();
    $alpha = seedTicketChangeCongregation('Alpha Hall');
    $beta = seedTicketChangeCongregation('Beta Hall');

    TicketChangeRequestModel::query()->create([
        'name' => 'Person A',
        'congregation' => $alpha->name,
        'notes' => 'Alpha person A needs a cancellation.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);
    TicketChangeRequestModel::query()->create([
        'name' => 'Person B',
        'congregation' => $alpha->name,
        'notes' => 'Alpha person B needs a registration change.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);
    TicketChangeRequestModel::query()->create([
        'name' => 'Person C',
        'congregation' => $beta->name,
        'notes' => 'Beta person needs help with parking.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequests::class)
        ->assertSeeInOrder([
            'Alpha Hall',
            'Person A',
            'Person B',
            'Beta Hall',
            'Person C',
        ])
        ->assertSee('2 people · 2 pending');
});

test('admin can mark all congregation pending requests completed', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Group Hall');
    $other = seedTicketChangeCongregation('Other Hall');

    $first = TicketChangeRequestModel::query()->create([
        'name' => 'Alex One',
        'congregation' => $cong->name,
        'notes' => 'Cancel ticket for AA11AAA.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);
    $second = TicketChangeRequestModel::query()->create([
        'name' => 'Blake Two',
        'congregation' => $cong->name,
        'notes' => 'Change registration to BB22BBB.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);
    $outsider = TicketChangeRequestModel::query()->create([
        'name' => 'Casey Other',
        'congregation' => $other->name,
        'notes' => 'Should remain pending outside the congregation.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $first])
        ->assertSee('Blake Two')
        ->assertSee('Change registration to BB22BBB.')
        ->call('markAllCongregationPendingCompleted')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.ticket-change-requests'));

    expect($first->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($second->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($outsider->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_PENDING);
});

test('admin can decline ticket change request and email inability to fulfil', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('Decline Hall');
    $registration = seedTicketChangeRegistration($cong);

    Setting::set(TicketEmailCcList::SETTING_KEY, "nathan-simpson@outlook.com\nops@example.com");

    $row = TicketChangeRequestModel::query()->create([
        'request_type' => TicketChangeRequestModel::TYPE_ADDITION,
        'name' => 'Space Seeker',
        'congregation' => $cong->name,
        'notification_email' => 'seeker@example.test',
        'notes' => 'Need an extra car park ticket.',
        'payload' => [
            'addition' => [
                'name' => 'Space Seeker',
                'contact_number' => '07700900111',
                'email' => 'seeker@example.test',
                'vehicle_type' => 'car',
                'vehicle_registration' => 'SS12SSS',
                'days' => ['Friday'],
                'elderly_infirm_parking' => false,
            ],
        ],
        'status' => TicketChangeRequestModel::STATUS_PENDING,
        'parking_registration_id' => $registration->id,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $row])
        ->call('decline')
        ->assertHasNoErrors();

    $row->refresh();
    expect($row->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($row->actioned_by)->toBe($admin->id)
        ->and($row->admin_notes)->toContain('spacing at Twickenham');

    Mail::assertSent(\App\Mail\TicketChangeRequestDeclinedMail::class, function (\App\Mail\TicketChangeRequestDeclinedMail $mail): bool {
        return $mail->hasTo('seeker@example.test')
            && in_array('nathan-simpson@outlook.com', $mail->ccAddresses, true)
            && in_array('ops@example.com', $mail->ccAddresses, true)
            && str_contains($mail->render(), 'unable to fulfil your request due to spacing at Twickenham');
    });
});

test('admin can decline an addition request from the list page', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('List Decline Hall');

    $row = TicketChangeRequestModel::query()->create([
        'request_type' => TicketChangeRequestModel::TYPE_ADDITION,
        'name' => 'Extra Space Seeker',
        'congregation' => $cong->name,
        'notification_email' => 'list-seeker@example.test',
        'notes' => 'Need an additional car park ticket for a visitor.',
        'payload' => [
            'addition' => [
                'name' => 'Extra Space Seeker',
                'contact_number' => '07700900222',
                'email' => 'list-seeker@example.test',
                'vehicle_type' => 'car',
                'vehicle_registration' => 'LS12ADD',
                'days' => ['Saturday'],
                'elderly_infirm_parking' => false,
            ],
        ],
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequests::class)
        ->assertSee(__('management.ticket_change_requests.decline'))
        ->call('decline', $row->id)
        ->assertHasNoErrors();

    $row->refresh();
    expect($row->status)->toBe(TicketChangeRequestModel::STATUS_COMPLETED)
        ->and($row->actioned_by)->toBe($admin->id);

    Mail::assertSent(\App\Mail\TicketChangeRequestDeclinedMail::class, function (\App\Mail\TicketChangeRequestDeclinedMail $mail): bool {
        return $mail->hasTo('list-seeker@example.test');
    });
});

test('decline requires a notification email', function () {
    $admin = User::factory()->admin()->create();
    $cong = seedTicketChangeCongregation('No Email Decline Hall');

    $row = TicketChangeRequestModel::query()->create([
        'request_type' => TicketChangeRequestModel::TYPE_ADDITION,
        'name' => 'No Email Person',
        'congregation' => $cong->name,
        'notification_email' => null,
        'notes' => 'Need an extra ticket.',
        'status' => TicketChangeRequestModel::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequestDetail::class, ['ticketChangeRequest' => $row])
        ->call('decline')
        ->assertHasErrors(['decline']);

    expect($row->fresh()->status)->toBe(TicketChangeRequestModel::STATUS_PENDING);
});

test('radisson hotel guest shortcut loads congregation and allows field update', function () {
    Mail::fake();

    $cong = \App\Models\HotelGuestParkingRequest::ensureCongregation();
    expect($cong->uuid)->toBe(\App\Models\HotelGuestParkingRequest::PUBLIC_CODE)
        ->and($cong->name)->toBe(\App\Models\HotelGuestParkingRequest::CONGREGATION_NAME);

    $registration = seedTicketChangeRegistration($cong, [
        'name' => 'Hotel Driver',
        'vehicle_registration' => 'HG99XYZ',
        'congregation' => \App\Models\HotelGuestParkingRequest::CONGREGATION_NAME,
    ]);

    $this->mock(MasterPassPdfGenerator::class, function ($mock) use ($registration): void {
        $mock->shouldReceive('generateForIds')
            ->once()
            ->with([$registration->id])
            ->andReturn([
                [
                    'filename' => 'Hotel Driver.pdf',
                    'content' => '%PDF-fake',
                    'registration' => $registration,
                ],
            ]);
    });

    Livewire::test(TicketChangeRequest::class)
        ->call('useRadissonHotelGuest')
        ->assertSet('congregationCode', 'RADISSON')
        ->assertSet('resolvedCongregation.name', \App\Models\HotelGuestParkingRequest::CONGREGATION_NAME)
        ->set('requestType', 'field_update')
        ->set('registrationSearch', 'HG99XYZ')
        ->assertSee('H***l D****r')
        ->assertSee('HG9*XYZ')
        ->assertDontSee('Hotel Driver')
        ->call('selectRegistration', $registration->id)
        ->assertSet('parkingRegistrationId', (string) $registration->id)
        ->assertSet('ownershipVerified', true)
        ->set('changeVehicleRegistration', true)
        ->set('newVehicleRegistration', 'HG11NEW')
        ->set('notificationEmail', 'hotel@example.test')
        ->set('notificationEmailConfirmation', 'hotel@example.test')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSet('submittedAutoApplied', true);

    expect($registration->fresh()->vehicle_registration)->toBe('HG11NEW');
});

test('ticket change request auto fills radisson code from guest query param', function () {
    \App\Models\HotelGuestParkingRequest::ensureCongregation();

    Livewire::withQueryParams(['guest' => 'radisson'])
        ->test(TicketChangeRequest::class)
        ->assertSet('congregationCode', 'RADISSON')
        ->assertSet('resolvedCongregation.name', \App\Models\HotelGuestParkingRequest::CONGREGATION_NAME);
});

test('ticket change request auto fills radisson code from code query param', function () {
    \App\Models\HotelGuestParkingRequest::ensureCongregation();

    Livewire::withQueryParams(['code' => 'radisson'])
        ->test(TicketChangeRequest::class)
        ->assertSet('congregationCode', 'RADISSON');
});
