<?php

use App\Livewire\Admin\TicketChangeRequestDetail;
use App\Livewire\Admin\TicketChangeRequests;
use App\Livewire\Public\TicketChangeRequest;
use App\Models\Congregation;
use App\Models\TicketChangeRequest as TicketChangeRequestModel;
use App\Models\User;
use Livewire\Livewire;

function seedTicketChangeCongregation(string $name = 'Change Req Hall'): Congregation
{
    return Congregation::query()->create([
        'name' => $name,
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);
}

test('ticket change request requires name congregation and notes', function () {
    Livewire::test(TicketChangeRequest::class)
        ->call('submit')
        ->assertHasErrors(['name', 'congregation', 'notes']);

    expect(TicketChangeRequestModel::query()->count())->toBe(0);
});

test('ticket change request rejects unknown congregation', function () {
    Livewire::test(TicketChangeRequest::class)
        ->set('name', 'Alex Driver')
        ->set('congregation', 'Does Not Exist Hall')
        ->set('notes', 'Please cancel my car park ticket for AB12CDE.')
        ->call('submit')
        ->assertHasErrors(['congregation']);

    expect(TicketChangeRequestModel::query()->count())->toBe(0);
});

test('ticket change request persists valid submission', function () {
    $cong = seedTicketChangeCongregation();

    Livewire::test(TicketChangeRequest::class)
        ->set('name', 'Alex Driver')
        ->set('congregation', $cong->name)
        ->set('notes', 'Please change vehicle registration from AB12CDE to XY99ZZZ.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $row = TicketChangeRequestModel::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->name)->toBe('Alex Driver')
        ->and($row->congregation)->toBe($cong->name)
        ->and($row->notes)->toContain('XY99ZZZ');
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
