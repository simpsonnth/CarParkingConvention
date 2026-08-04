<?php

use App\Livewire\Admin\Congregations;
use App\Models\Congregation;
use App\Models\User;
use Livewire\Livewire;

test('congregation jwpub email is derived from code', function () {
    $congregation = Congregation::query()->create([
        'name' => 'Email Hall',
        'uuid' => '0987',
    ]);

    expect($congregation->jwpubEmail())->toBe('CONG0970987@jwpub.org');
});

test('admin can build comma-separated congregation emails for To field', function () {
    $admin = User::factory()->admin()->create();

    Congregation::query()->create([
        'name' => 'Zeta Hall',
        'uuid' => '956754',
    ]);
    Congregation::query()->create([
        'name' => 'Alpha Hall',
        'uuid' => '0987',
    ]);

    Livewire::actingAs($admin)
        ->test(Congregations::class)
        ->assertSee('CONG0970987@jwpub.org')
        ->assertSee('CONG097956754@jwpub.org')
        ->assertSee('Copy emails for To');

    $component = Livewire::actingAs($admin)->test(Congregations::class);
    $list = $component->instance()->getJwpubEmailsList();

    expect($list)->toBe('CONG0970987@jwpub.org, CONG097956754@jwpub.org');
});

test('congregation email list respects search filter', function () {
    $admin = User::factory()->admin()->create();

    Congregation::query()->create([
        'name' => 'Alpha Hall',
        'uuid' => '0987',
    ]);
    Congregation::query()->create([
        'name' => 'Beta Hall',
        'uuid' => '956754',
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Congregations::class)
        ->set('search', 'Alpha');

    expect($component->instance()->getJwpubEmailsList())->toBe('CONG0970987@jwpub.org');
});

test('admin can download congregation emails as plain text', function () {
    $admin = User::factory()->admin()->create();

    Congregation::query()->create([
        'name' => 'Alpha Hall',
        'uuid' => '0987',
    ]);
    Congregation::query()->create([
        'name' => 'Beta Hall',
        'uuid' => '956754',
    ]);

    Livewire::actingAs($admin)
        ->test(Congregations::class)
        ->call('openDownloadEmailsModal')
        ->assertSet('downloadEmailsModalOpen', true)
        ->assertSet('emailExportFormat', 'comma')
        ->call('downloadJwpubEmails')
        ->assertFileDownloaded(
            content: 'CONG0970987@jwpub.org, CONG097956754@jwpub.org',
            contentType: 'text/plain; charset=UTF-8',
        );
});

test('admin can download congregation emails one per line', function () {
    $admin = User::factory()->admin()->create();

    Congregation::query()->create([
        'name' => 'Alpha Hall',
        'uuid' => '0987',
    ]);
    Congregation::query()->create([
        'name' => 'Beta Hall',
        'uuid' => '956754',
    ]);

    Livewire::actingAs($admin)
        ->test(Congregations::class)
        ->call('openDownloadEmailsModal')
        ->set('emailExportFormat', 'newline')
        ->call('downloadJwpubEmails')
        ->assertFileDownloaded(
            content: "CONG0970987@jwpub.org\nCONG097956754@jwpub.org",
            contentType: 'text/plain; charset=UTF-8',
        );
});

test('admin congregations page can show all rows per page', function () {
    $admin = User::factory()->admin()->create();

    foreach (range(1, 30) as $i) {
        Congregation::query()->create([
            'name' => sprintf('Hall %02d', $i),
            'uuid' => 'code-'.$i,
        ]);
    }

    Livewire::actingAs($admin)
        ->test(Congregations::class)
        ->assertSee('Hall 01')
        ->assertDontSee('Hall 30')
        ->set('perPage', 'all')
        ->assertSee('Hall 01')
        ->assertSee('Hall 30');
});

test('renaming a congregation updates matching parking registration names', function () {
    $admin = User::factory()->admin()->create();

    $congregation = Congregation::query()->create([
        'name' => 'Old Hall Name',
        'uuid' => 'old-hall-code',
    ]);

    $registration = \App\Models\ParkingRegistration::query()->create([
        'name' => 'Driver',
        'congregation' => 'Old Hall Name',
        'contact_number' => '07700900999',
        'email' => 'driver@rename.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'RN01AAA',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Congregations::class)
        ->call('edit', $congregation)
        ->set('name', 'New Hall Name')
        ->set('code', 'old-hall-code')
        ->call('save')
        ->assertHasNoErrors();

    expect($congregation->fresh()->name)->toBe('New Hall Name');
    expect($registration->fresh()->congregation)->toBe('New Hall Name');
});
