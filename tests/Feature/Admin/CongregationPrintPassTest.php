<?php

use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\User;

test('congregation print page includes back page with map and congregation note but no personal contact', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Congregation Map Park',
        'capacity' => 40,
        'location' => 'Main entrance',
        'color' => '#7c3aed',
        'map_image_path' => '/storage/car-park-maps/cong-map.jpg',
        'travel_directions' => 'Congregation park travel directions',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Zeta Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.congregations.print', $congregation))
        ->assertOk()
        ->assertSeeHtml('class="pass-back-outer"')
        ->assertSee(__('print_pass.back_heading'))
        ->assertSee(__('print_pass.congregation_pass_note'))
        ->assertSee(__('print_pass.footwear_note'))
        ->assertSee(__('print_pass.scripture_text'))
        ->assertSee(__('print_pass.closing'))
        ->assertSee('/storage/car-park-maps/cong-map.jpg')
        ->assertSee(__('print_pass.travel_directions'))
        ->assertSee('Congregation park travel directions')
        ->assertDontSee(__('print_pass.emergency_contact_note'))
        ->assertDontSee(__('print_pass.registrant_name').':');
});
