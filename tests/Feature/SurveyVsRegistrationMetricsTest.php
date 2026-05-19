<?php

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use App\Services\SurveyVsRegistrationMetrics;
use Illuminate\Support\Str;

test('survey allocation includes coach slot when survey organises coach', function () {
    $uuid = (string) Str::uuid();
    $cong = Congregation::query()->create(['name' => 'Coach Hall', 'uuid' => $uuid]);
    CongregationNumbersResponse::query()->create([
        'congregation_id' => $cong->id,
        'car_park_tickets_count' => 2,
        'organizes_coach' => true,
        'disabled_parking_required' => false,
        'disabled_parking_count' => 0,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Car One',
        'congregation' => $cong->name,
        'contact_number' => '111',
        'vehicle_registration' => 'AA11AAA',
        'days' => ['Friday'],
        'email' => 'car1@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);
    ParkingRegistration::query()->create([
        'name' => 'Car Two',
        'congregation' => $cong->name,
        'contact_number' => '222',
        'vehicle_registration' => 'BB22BBB',
        'days' => ['Friday'],
        'email' => 'car2@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);
    ParkingRegistration::query()->create([
        'name' => 'Coach Captain',
        'congregation' => $cong->name,
        'contact_number' => '333',
        'vehicle_registration' => null,
        'days' => ['Friday', 'Saturday'],
        'email' => 'coach@example.test',
        'vehicle_type' => 'coach',
        'elderly_infirm_parking' => false,
    ]);

    $metrics = app(SurveyVsRegistrationMetrics::class)->compute();
    $row = collect($metrics['rows'])->firstWhere('name', $cong->name);

    expect($row)->not->toBeNull()
        ->and($row['survey_tickets'])->toBe(3)
        ->and($row['registration_count'])->toBe(3)
        ->and($row['difference'])->toBe(0)
        ->and($row['progress_percent'])->toBe(100.0);
});
