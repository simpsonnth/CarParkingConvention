<?php

use App\Models\ParkingRegistration;
use App\Services\ParkingRegistrationAttendanceByDayMetrics;

function createParkingRegistrationForAttendanceTest(array $days, array $overrides = []): ParkingRegistration
{
    return ParkingRegistration::query()->create(array_merge([
        'name' => 'Test User',
        'congregation' => 'Test Congregation',
        'contact_number' => '111',
        'vehicle_registration' => 'TT'.uniqid('', true),
        'days' => $days,
        'email' => 'test-'.uniqid('', true).'@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ], $overrides));
}

test('attendance by day counts registrations per convention day and missing days', function () {
    createParkingRegistrationForAttendanceTest(['Friday']);
    createParkingRegistrationForAttendanceTest(['Friday', 'Saturday']);
    createParkingRegistrationForAttendanceTest([]);

    $m = app(ParkingRegistrationAttendanceByDayMetrics::class)->compute();

    expect($m['counts_by_day']['Friday'])->toBe(2)
        ->and($m['counts_by_day']['Saturday'])->toBe(1)
        ->and($m['counts_by_day']['Sunday'])->toBe(0)
        ->and($m['total_registrations'])->toBe(3)
        ->and($m['missing_days_count'])->toBe(1);
});

test('attendance by day breaks down cars coaches and elderly infirm', function () {
    createParkingRegistrationForAttendanceTest(['Friday'], ['vehicle_type' => 'coach']);
    createParkingRegistrationForAttendanceTest(['Friday'], ['elderly_infirm_parking' => true]);
    createParkingRegistrationForAttendanceTest(['Friday', 'Saturday'], ['vehicle_type' => 'car']);

    $m = app(ParkingRegistrationAttendanceByDayMetrics::class)->compute();

    expect($m['counts_by_day']['Friday'])->toBe(3)
        ->and($m['cars_by_day']['Friday'])->toBe(2)
        ->and($m['coaches_by_day']['Friday'])->toBe(1)
        ->and($m['disabled_by_day']['Friday'])->toBe(1)
        ->and($m['counts_by_day']['Saturday'])->toBe(1)
        ->and($m['cars_by_day']['Saturday'])->toBe(1)
        ->and($m['coaches_by_day']['Saturday'])->toBe(0)
        ->and($m['disabled_by_day']['Saturday'])->toBe(0);

    foreach (['Friday', 'Saturday', 'Sunday'] as $day) {
        expect($m['cars_by_day'][$day] + $m['coaches_by_day'][$day])->toBe($m['counts_by_day'][$day]);
    }
});

test('attendance by day counts circuit overseer registrations per day', function () {
    createParkingRegistrationForAttendanceTest(['Friday'], ['is_circuit_overseer' => true]);
    createParkingRegistrationForAttendanceTest(['Friday', 'Sunday'], ['is_circuit_overseer' => true]);

    $m = app(ParkingRegistrationAttendanceByDayMetrics::class)->compute();

    expect($m['circuit_overseers_by_day']['Friday'])->toBe(2)
        ->and($m['circuit_overseers_by_day']['Saturday'])->toBe(0)
        ->and($m['circuit_overseers_by_day']['Sunday'])->toBe(1)
        ->and($m['counts_by_day']['Friday'])->toBe(2);
});

test('soft deleted registrations are excluded from attendance metrics', function () {
    $deleted = createParkingRegistrationForAttendanceTest(['Sunday']);
    $deleted->delete();
    createParkingRegistrationForAttendanceTest(['Sunday']);

    $m = app(ParkingRegistrationAttendanceByDayMetrics::class)->compute();

    expect($m['counts_by_day']['Sunday'])->toBe(1)
        ->and($m['total_registrations'])->toBe(1);
});
