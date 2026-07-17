<?php

use App\Livewire\Admin\CarParkDetail;
use App\Livewire\Admin\CarParks;
use App\Models\CarPark;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('car park save persists uploaded map image path', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CarParks::class)
        ->set('name', 'South Car Park')
        ->set('capacity', 50)
        ->set('location', 'Behind main hall')
        ->set('color', '#22c55e')
        ->set('mapImage', UploadedFile::fake()->image('parking-map.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $carPark = CarPark::query()->where('name', 'South Car Park')->first();

    expect($carPark)->not->toBeNull();
    expect($carPark->map_image_path)->toStartWith('/storage/car-park-maps/');
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $carPark->map_image_path));
});

test('car park create persists travel directions', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(CarParks::class)
        ->set('name', 'Directions Park')
        ->set('capacity', 40)
        ->set('location', 'West')
        ->set('color', '#2563eb')
        ->set('travelDirections', "Enter via Gate B.\nFollow blue signs.")
        ->call('save')
        ->assertHasNoErrors();

    $carPark = CarPark::query()->where('name', 'Directions Park')->first();

    expect($carPark)->not->toBeNull();
    expect($carPark->travel_directions)->toBe("Enter via Gate B.\nFollow blue signs.");
});

test('car park forms show travel directions formatting controls', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $carPark = CarPark::query()->create([
        'name' => 'Formatted Park',
        'capacity' => 40,
        'location' => 'West',
        'color' => '#2563eb',
    ]);

    Livewire::test(CarParks::class)
        ->call('edit', $carPark)
        ->assertSee('Heading')
        ->assertSee('Subheading')
        ->assertSee('Bold')
        ->assertSeeHtml('maxlength="2000"');

    Livewire::test(CarParkDetail::class, ['carPark' => $carPark])
        ->call('edit')
        ->assertSee('Heading')
        ->assertSee('Subheading')
        ->assertSee('Bold')
        ->assertSeeHtml('maxlength="2000"');
});

test('car park edit replaces map image and deletes old file', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $oldPath = 'car-park-maps/old-map.jpg';
    Storage::disk('public')->put($oldPath, 'old-image');

    $carPark = CarPark::query()->create([
        'name' => 'Replace Map Park',
        'capacity' => 30,
        'location' => 'North',
        'color' => '#dc2626',
        'map_image_path' => '/storage/'.$oldPath,
    ]);

    Livewire::test(CarParks::class)
        ->call('edit', $carPark)
        ->set('mapImage', UploadedFile::fake()->image('new-map.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $carPark->refresh();

    expect($carPark->map_image_path)->not->toBe('/storage/'.$oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $carPark->map_image_path));
});

test('car park detail edit replaces map image and deletes old file', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $oldPath = 'car-park-maps/detail-old-map.jpg';
    Storage::disk('public')->put($oldPath, 'old-image');

    $carPark = CarPark::query()->create([
        'name' => 'Detail Replace Park',
        'capacity' => 25,
        'location' => 'East',
        'color' => '#7c3aed',
        'map_image_path' => '/storage/'.$oldPath,
    ]);

    $component = Livewire::test(CarParkDetail::class, ['carPark' => $carPark])
        ->call('edit')
        ->set('mapImage', UploadedFile::fake()->image('detail-new-map.jpg'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('mapImage', null);

    $carPark->refresh();

    expect($carPark->map_image_path)->not->toBe('/storage/'.$oldPath);
    expect($component->get('existingMapImage'))->toBe($carPark->map_image_path);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $carPark->map_image_path));
});

test('car park edit without new map leaves existing image untouched', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $oldPath = 'car-park-maps/keep-map.jpg';
    Storage::disk('public')->put($oldPath, 'keep-image');

    $carPark = CarPark::query()->create([
        'name' => 'Keep Map Park',
        'capacity' => 20,
        'location' => 'South',
        'color' => '#ea580c',
        'map_image_path' => '/storage/'.$oldPath,
    ]);

    Livewire::test(CarParks::class)
        ->call('edit', $carPark)
        ->set('name', 'Keep Map Park Updated')
        ->call('save')
        ->assertHasNoErrors();

    $carPark->refresh();

    expect($carPark->name)->toBe('Keep Map Park Updated');
    expect($carPark->map_image_path)->toBe('/storage/'.$oldPath);
    Storage::disk('public')->assertExists($oldPath);
});

test('car park list and detail edit persist travel directions', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $carPark = CarPark::query()->create([
        'name' => 'Editable Directions Park',
        'capacity' => 15,
        'location' => 'North',
        'color' => '#0f766e',
    ]);

    Livewire::test(CarParks::class)
        ->call('edit', $carPark)
        ->set('travelDirections', 'List modal directions')
        ->call('save')
        ->assertHasNoErrors();

    expect($carPark->fresh()->travel_directions)->toBe('List modal directions');

    Livewire::test(CarParkDetail::class, ['carPark' => $carPark->fresh()])
        ->call('edit')
        ->set('travelDirections', 'Detail modal directions')
        ->call('save')
        ->assertHasNoErrors();

    expect($carPark->fresh()->travel_directions)->toBe('Detail modal directions');
});

test('empty travel directions persist as null', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $carPark = CarPark::query()->create([
        'name' => 'Clear Directions Park',
        'capacity' => 12,
        'location' => 'West',
        'color' => '#334155',
        'travel_directions' => 'Old directions',
    ]);

    Livewire::test(CarParks::class)
        ->call('edit', $carPark)
        ->set('travelDirections', '   ')
        ->call('save')
        ->assertHasNoErrors();

    expect($carPark->fresh()->travel_directions)->toBeNull();
});

test('travel directions longer than 2000 characters fail validation', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(CarParks::class)
        ->set('name', 'Long Directions Park')
        ->set('capacity', 10)
        ->set('travelDirections', str_repeat('a', 2001))
        ->call('save')
        ->assertHasErrors(['travelDirections' => 'max']);
});

test('user with only car parks view cannot mutate parks', function () {
    $viewer = User::factory()->attendant()->create();
    $viewer->givePermissionTo('car-parks.view');

    $carPark = CarPark::query()->create([
        'name' => 'Protected Park',
        'capacity' => 10,
        'location' => 'Gate',
        'color' => '#111827',
    ]);

    $this->actingAs($viewer);

    Livewire::test(CarParks::class)
        ->call('edit', $carPark)
        ->assertForbidden();

    Livewire::test(CarParks::class)
        ->set('name', 'Hacked Park')
        ->set('capacity', 99)
        ->call('save')
        ->assertForbidden();

    Livewire::test(CarParkDetail::class, ['carPark' => $carPark])
        ->call('edit')
        ->assertForbidden();

    expect(CarPark::query()->where('name', 'Hacked Park')->exists())->toBeFalse();
});

test('car park map image accepts files up to 10MB', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(CarParks::class)
        ->set('name', 'Large Map Park')
        ->set('capacity', 40)
        ->set('mapImage', UploadedFile::fake()->image('large-map.jpg')->size(10240))
        ->call('save')
        ->assertHasNoErrors();

    expect(CarPark::query()->where('name', 'Large Map Park')->exists())->toBeTrue();
});

test('car park map image rejects files over 10MB', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(CarParks::class)
        ->set('name', 'Too Large Map Park')
        ->set('capacity', 40)
        ->set('mapImage', UploadedFile::fake()->image('too-large.jpg')->size(10241))
        ->call('save')
        ->assertHasErrors(['mapImage' => 'max']);
});
