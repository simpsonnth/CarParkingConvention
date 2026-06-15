<?php

use App\Livewire\Admin\LessonsLearned;
use App\Livewire\Admin\ParkingIncidents;
use App\Livewire\Admin\ToolboxFeedbackAdmin;
use App\Models\LessonLearned;
use App\Models\ParkingIncidentReport;
use App\Models\ToolboxFeedback;
use App\Support\ConventionDay;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access management admin pages', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.parking-incidents'))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($user)
        ->get(route('admin.toolbox-feedback'))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($user)
        ->get(route('admin.lessons-learned'))
        ->assertRedirect(route('dashboard'));
});

test('admin can view parking incidents list', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    ParkingIncidentReport::query()->create([
        'type' => ParkingIncidentReport::TYPE_NEAR_MISS,
        'occurred_at' => now(),
        'location' => 'Gate A',
        'description' => 'Vehicle stopped abruptly.',
        'injury_reported' => false,
        'reporter_name' => 'Reporter',
        'reporter_email' => 'rep@example.test',
        'reporter_phone' => '07000000000',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.parking-incidents'))
        ->assertOk()
        ->assertSee('Reporter');

    Livewire::actingAs($admin)
        ->test(ParkingIncidents::class)
        ->call('openDetail', ParkingIncidentReport::query()->value('id'))
        ->assertSet('detailModalOpen', true);
});

test('admin can view toolbox feedback list', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    ToolboxFeedback::query()->create([
        'submitter_name' => 'Feedback User',
        'submitter_email' => 'fb@example.test',
        'feedback' => 'Talk about wet weather procedures.',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.toolbox-feedback'))
        ->assertOk()
        ->assertSee('Feedback User')
        ->assertSee(__('toolbox_talk_reminders.button'));
});

test('admin can open toolbox talk reminders modal', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(ToolboxFeedbackAdmin::class)
        ->set('remindersModalOpen', true)
        ->assertSee(__('toolbox_talk_reminders.modal_title'))
        ->assertSee(__('toolbox_talk_reminders.sections.event_profile.title'))
        ->assertSee(__('toolbox_talk_reminders.sections.security_hvm.title'));
});

test('admin can create toolbox feedback', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(ToolboxFeedbackAdmin::class)
        ->call('openCreate')
        ->set('formSubmitterName', 'Verbal Suggestion')
        ->set('formSubmitterEmail', 'verbal@example.test')
        ->set('formFeedback', 'Cover radio check procedure at the start of each shift.')
        ->call('save')
        ->assertSet('formModalOpen', false);

    expect(ToolboxFeedback::query()->count())->toBe(1);
    expect(ToolboxFeedback::query()->value('submitter_name'))->toBe('Verbal Suggestion');
});

test('admin can mark toolbox feedback as added to talk with day', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $id = ToolboxFeedback::query()->create([
        'submitter_name' => 'Attendee',
        'submitter_email' => 'a@example.test',
        'feedback' => 'Discuss wet weather marshalling.',
    ])->id;

    Livewire::actingAs($admin)
        ->test(ToolboxFeedbackAdmin::class)
        ->call('openEdit', $id)
        ->set('formAddedToToolboxTalk', '1')
        ->set('formToolboxTalkDay', 'Saturday')
        ->call('save');

    $row = ToolboxFeedback::query()->findOrFail($id);
    expect($row->added_to_toolbox_talk)->toBeTrue();
    expect($row->toolbox_talk_day)->toBe('Saturday');
});

test('admin can create edit and delete lessons learned', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(LessonsLearned::class)
        ->call('openCreate')
        ->set('formReporterName', 'Admin Note')
        ->set('formCategory', LessonLearned::CATEGORY_PARKING)
        ->set('formConventionDay', ConventionDay::FRIDAY)
        ->set('formTitle', 'Marshals')
        ->set('formWorkedWell', 'High-vis vests were visible.')
        ->call('save')
        ->assertSet('formModalOpen', false);

    $lesson = LessonLearned::query()->first();
    expect($lesson)->not->toBeNull();
    expect($lesson->source)->toBe(LessonLearned::SOURCE_ADMIN);
    expect($lesson->convention_day)->toBe(ConventionDay::FRIDAY);
    expect($lesson->created_by_user_id)->toBe($admin->id);

    Livewire::actingAs($admin)
        ->test(LessonsLearned::class)
        ->call('openEdit', $lesson->id)
        ->set('formDidntWorkWell', 'Radios were low on battery.')
        ->call('save');

    expect($lesson->fresh()->didnt_work_well)->toBe('Radios were low on battery.');

    Livewire::actingAs($admin)
        ->test(LessonsLearned::class)
        ->call('delete', $lesson->id);

    expect(LessonLearned::query()->count())->toBe(0);
});
