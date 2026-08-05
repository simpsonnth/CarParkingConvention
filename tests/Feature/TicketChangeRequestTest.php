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
    ]);

    Livewire::actingAs($admin)
        ->test(TicketChangeRequests::class)
        ->assertSee('Sam Parker')
        ->assertSee('Admin View Hall')
        ->call('openDetail', TicketChangeRequestModel::query()->first()->id)
        ->assertSet('detailModalOpen', true)
        ->assertSee('Cancel ticket for vehicle AB12CDE please.');
});
