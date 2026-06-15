<?php

use App\Livewire\Public\LessonLearned;
use App\Livewire\Public\ParkingIncidentReport;
use App\Livewire\Public\ToolboxFeedback;
use App\Models\CarPark;
use App\Models\LessonLearned as LessonLearnedModel;
use App\Models\ParkingIncidentReport as ParkingIncidentReportModel;
use App\Models\ToolboxFeedback as ToolboxFeedbackModel;
use App\Support\ConventionDay;
use Livewire\Livewire;

test('parking incident report persists valid submission', function () {
    $park = CarPark::query()->create([
        'name' => 'North Lot',
        'capacity' => 100,
        'location' => 'North',
        'color' => '#336699',
    ]);

    Livewire::test(ParkingIncidentReport::class)
        ->set('type', ParkingIncidentReportModel::TYPE_NEAR_MISS)
        ->set('occurredAt', now()->format('Y-m-d\TH:i'))
        ->set('location', 'Row B entrance')
        ->set('carParkId', (string) $park->id)
        ->set('description', 'A vehicle reversed without checking mirrors.')
        ->set('injuryReported', '0')
        ->set('reporterName', 'Jane Parking')
        ->set('reporterEmail', 'jane@example.test')
        ->set('reporterPhone', '07111222333')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(ParkingIncidentReportModel::query()->count())->toBe(1);
    $row = ParkingIncidentReportModel::query()->first();
    expect($row->car_park_id)->toBe($park->id);
    expect($row->reporter_email)->toBe('jane@example.test');
});

test('parking incident report requires severity for accidents', function () {
    Livewire::test(ParkingIncidentReport::class)
        ->set('type', ParkingIncidentReportModel::TYPE_ACCIDENT)
        ->set('occurredAt', now()->format('Y-m-d\TH:i'))
        ->set('location', 'Main gate')
        ->set('description', 'Minor collision between two vehicles.')
        ->set('injuryReported', '0')
        ->set('reporterName', 'Jane Parking')
        ->set('reporterEmail', 'jane@example.test')
        ->set('reporterPhone', '07111222333')
        ->call('submit')
        ->assertHasErrors('severity');

    expect(ParkingIncidentReportModel::query()->count())->toBe(0);
});

test('parking incident report rejects invalid car park', function () {
    Livewire::test(ParkingIncidentReport::class)
        ->set('type', ParkingIncidentReportModel::TYPE_NEAR_MISS)
        ->set('occurredAt', now()->format('Y-m-d\TH:i'))
        ->set('location', 'Row B')
        ->set('carParkId', '99999')
        ->set('description', 'Near miss at the end of the row.')
        ->set('reporterName', 'Jane Parking')
        ->set('reporterEmail', 'jane@example.test')
        ->set('reporterPhone', '07111222333')
        ->call('submit')
        ->assertHasErrors('carParkId');
});

test('toolbox feedback requires name and email', function () {
    Livewire::test(ToolboxFeedback::class)
        ->set('feedback', 'Discuss high-traffic flow at 8am.')
        ->call('submit')
        ->assertHasErrors(['submitterName', 'submitterEmail']);

    expect(ToolboxFeedbackModel::query()->count())->toBe(0);
});

test('toolbox feedback persists valid submission', function () {
    Livewire::test(ToolboxFeedback::class)
        ->set('submitterName', 'John Attendee')
        ->set('submitterEmail', 'john@example.test')
        ->set('feedback', 'Please cover emergency evacuation routes in the toolbox talk.')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(ToolboxFeedbackModel::query()->count())->toBe(1);
});

test('lesson learned public submit requires content', function () {
    Livewire::test(LessonLearned::class)
        ->set('reporterName', 'Parking Volunteer')
        ->call('submit')
        ->assertHasErrors('workedWell');

    expect(LessonLearnedModel::query()->count())->toBe(0);
});

test('lesson learned public submit persists', function () {
    Livewire::test(LessonLearned::class)
        ->set('reporterName', 'Parking Volunteer')
        ->set('conventionDay', ConventionDay::SATURDAY)
        ->set('workedWell', 'Clear signage helped drivers find spaces.')
        ->call('submit')
        ->assertSet('submitted', true);

    $lesson = LessonLearnedModel::query()->first();
    expect($lesson)->not->toBeNull();
    expect($lesson->source)->toBe(LessonLearnedModel::SOURCE_PUBLIC);
    expect($lesson->convention_day)->toBe(ConventionDay::SATURDAY);
});
