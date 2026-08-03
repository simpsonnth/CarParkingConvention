<?php

declare(strict_types=1);

use App\Livewire\Admin\Registrations;
use App\Models\ParkingRegistration;
use App\Models\User;
use App\Services\ParkingRegistrationListQuery;
use App\Support\ParkingRegistrationListFilters;
use Livewire\Livewire;

test('registrations search finds by padded ticket number', function () {
    $admin = User::factory()->admin()->create();

    $target = ParkingRegistration::query()->create([
        'name' => 'Ticket Target',
        'congregation' => 'Ticket Hall',
        'contact_number' => '07700000901',
        'email' => 'ticket-target@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'TK01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Other Person',
        'congregation' => 'Other Hall',
        'contact_number' => '07700000902',
        'email' => 'other-person@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'OT02BBB',
        'days' => ['Friday'],
    ]);

    $ticketNumber = $target->ticketNumber();

    $rows = app(ParkingRegistrationListQuery::class)->apply(
        ParkingRegistration::query(),
        new ParkingRegistrationListFilters(search: $ticketNumber)
    )->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()?->id)->toBe($target->id);

    $rowsByRawId = app(ParkingRegistrationListQuery::class)->apply(
        ParkingRegistration::query(),
        new ParkingRegistrationListFilters(search: (string) $target->id)
    )->get();

    expect($rowsByRawId)->toHaveCount(1)
        ->and($rowsByRawId->first()?->id)->toBe($target->id);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('search', $ticketNumber)
        ->assertSee('Ticket Target')
        ->assertDontSee('Other Person');
});
