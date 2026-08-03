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
        ->call('downloadJwpubEmails')
        ->assertFileDownloaded(
            content: 'CONG0970987@jwpub.org, CONG097956754@jwpub.org',
            contentType: 'text/plain; charset=UTF-8',
        );
});
