<?php

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

test('admin can view ticket change requests', function () {
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
        ->call('openDetail', TicketChangeRequestModel::query()->first()->id)
        ->assertSet('detailModalOpen', true)
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
