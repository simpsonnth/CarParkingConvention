<?php

use App\Livewire\Admin\Registrations;
use App\Models\ParkingRegistration;
use App\Models\User;
use App\Services\ParkingRegistrationListQuery;
use App\Support\ParkingRegistrationListFilters;
use Livewire\Livewire;

function createTicketSentTestRegistration(string $suffix = 'a'): ParkingRegistration
{
    return ParkingRegistration::query()->create([
        'name' => 'Person '.$suffix,
        'congregation' => 'Sent Test Hall',
        'contact_number' => '07700900'.substr(md5($suffix), 0, 3),
        'vehicle_registration' => strtoupper(substr(md5($suffix), 0, 7)),
        'days' => ['Friday'],
        'email' => 'person-'.$suffix.'@sent.example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
        'ticket_sent_at' => null,
    ]);
}

test('opening download modal requires selection and does not mark sent', function () {
    $admin = User::factory()->admin()->create();
    $reg = createTicketSentTestRegistration('open');

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('openDownloadMasterPassesModal')
        ->assertSet('downloadPassesModalOpen', false);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('selectedIds', [(string) $reg->id])
        ->call('openDownloadMasterPassesModal')
        ->assertSet('downloadPassesModalOpen', true);

    expect($reg->fresh()->ticket_sent_at)->toBeNull();
});

test('confirm download with mark sent stamps ticket_sent_at and redirects to zip route', function () {
    $admin = User::factory()->admin()->create();
    $reg = createTicketSentTestRegistration('yes');
    $other = createTicketSentTestRegistration('other');

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('selectedIds', [(string) $reg->id])
        ->set('downloadPassesModalOpen', true)
        ->call('confirmDownloadMasterPassesZip', true)
        ->assertRedirectContains('/admin/registrations/download-master-passes-zip/');

    expect($reg->fresh()->ticket_sent_at)->not->toBeNull();
    expect($other->fresh()->ticket_sent_at)->toBeNull();
});

test('confirm download without mark leaves ticket_sent_at null and still redirects', function () {
    $admin = User::factory()->admin()->create();
    $reg = createTicketSentTestRegistration('no');

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('selectedIds', [(string) $reg->id])
        ->set('downloadPassesModalOpen', true)
        ->call('confirmDownloadMasterPassesZip', false)
        ->assertRedirectContains('/admin/registrations/download-master-passes-zip/');

    expect($reg->fresh()->ticket_sent_at)->toBeNull();
});

test('closing download modal without confirm does not mark sent or redirect', function () {
    $admin = User::factory()->admin()->create();
    $reg = createTicketSentTestRegistration('cancel');

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('selectedIds', [(string) $reg->id])
        ->call('openDownloadMasterPassesModal')
        ->assertSet('downloadPassesModalOpen', true)
        ->set('downloadPassesModalOpen', false)
        ->assertSet('downloadPassesModalOpen', false)
        ->assertSet('selectedIds', [(string) $reg->id]);

    expect($reg->fresh()->ticket_sent_at)->toBeNull();
});

test('bulk mark and clear ticket sent update only selected rows', function () {
    $admin = User::factory()->admin()->create();
    $a = createTicketSentTestRegistration('bulk-a');
    $b = createTicketSentTestRegistration('bulk-b');
    $c = createTicketSentTestRegistration('bulk-c');

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('selectedIds', [(string) $a->id, (string) $b->id])
        ->call('bulkMarkTicketSent')
        ->assertSet('selectedIds', []);

    expect($a->fresh()->ticket_sent_at)->not->toBeNull();
    expect($b->fresh()->ticket_sent_at)->not->toBeNull();
    expect($c->fresh()->ticket_sent_at)->toBeNull();

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('selectedIds', [(string) $a->id])
        ->call('bulkClearTicketSent')
        ->assertSet('selectedIds', []);

    expect($a->fresh()->ticket_sent_at)->toBeNull();
    expect($b->fresh()->ticket_sent_at)->not->toBeNull();
});

test('list query filters by ticket sent status', function () {
    $sent = createTicketSentTestRegistration('filter-sent');
    $sent->update(['ticket_sent_at' => now()]);
    $notSent = createTicketSentTestRegistration('filter-not');

    $query = app(ParkingRegistrationListQuery::class);

    $sentIds = $query->apply(
        ParkingRegistration::query(),
        new ParkingRegistrationListFilters(ticketSent: true)
    )->pluck('id')->all();

    $notSentIds = $query->apply(
        ParkingRegistration::query(),
        new ParkingRegistrationListFilters(ticketSent: false)
    )->pluck('id')->all();

    expect($sentIds)->toContain($sent->id);
    expect($sentIds)->not->toContain($notSent->id);
    expect($notSentIds)->toContain($notSent->id);
    expect($notSentIds)->not->toContain($sent->id);
});

test('filter drawer ticket sent apply shows only matching registrations', function () {
    $admin = User::factory()->admin()->create();
    $sent = createTicketSentTestRegistration('drawer-sent');
    $sent->update(['ticket_sent_at' => now()]);
    $notSent = createTicketSentTestRegistration('drawer-not');

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('openFilterPanel')
        ->set('filterDraftTicketSent', '0')
        ->call('applyFilters')
        ->assertSet('filterTicketSent', false)
        ->assertSee($notSent->name)
        ->assertDontSee($sent->name)
        ->call('openFilterPanel')
        ->set('filterDraftTicketSent', '1')
        ->call('applyFilters')
        ->assertSet('filterTicketSent', true)
        ->assertSee($sent->name)
        ->assertDontSee($notSent->name)
        ->call('openFilterPanel')
        ->set('filterDraftTicketSent', 'any')
        ->call('applyFilters')
        ->assertSet('filterTicketSent', null)
        ->assertSee($sent->name)
        ->assertSee($notSent->name);
});
