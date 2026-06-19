<?php

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
