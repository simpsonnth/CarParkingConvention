<?php

declare(strict_types=1);

use App\Livewire\Admin\LessonsLearned;
use App\Livewire\Public\LessonLearned;
use App\Models\LessonLearned as LessonLearnedModel;
use App\Models\LessonLearnedAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    config(['lessons-learned.disk' => 'local']);
});

test('public lesson submission stores uploaded files on the attachments disk', function () {
    $file = UploadedFile::fake()->create('notes.pdf', 120, 'application/pdf');

    Livewire::test(LessonLearned::class)
        ->set('reporterName', 'Volunteer')
        ->set('workedWell', 'Signage was clear')
        ->set('attachments', [$file])
        ->call('submit')
        ->assertSet('submitted', true)
        ->assertHasNoErrors();

    $lesson = LessonLearnedModel::query()->first();
    expect($lesson)->not->toBeNull();

    $attachment = LessonLearnedAttachment::query()->first();
    expect($attachment)->not->toBeNull()
        ->and($attachment->lesson_learned_id)->toBe($lesson->id)
        ->and($attachment->kind)->toBe(LessonLearnedAttachment::KIND_FILE)
        ->and($attachment->original_name)->toBe('notes.pdf')
        ->and($attachment->disk)->toBe('local');

    Storage::disk('local')->assertExists($attachment->path);
});

test('public lesson submission stores voice notes', function () {
    $voice = UploadedFile::fake()->create('voice-note.webm', 80, 'audio/webm');

    Livewire::test(LessonLearned::class)
        ->set('reporterName', 'Volunteer')
        ->set('didntWorkWell', 'Queue was long')
        ->set('voiceNotes', [$voice])
        ->call('submit')
        ->assertSet('submitted', true);

    $attachment = LessonLearnedAttachment::query()->first();
    expect($attachment)->not->toBeNull()
        ->and($attachment->kind)->toBe(LessonLearnedAttachment::KIND_VOICE_NOTE)
        ->and($attachment->original_name)->toBe('voice-note.webm');

    Storage::disk('local')->assertExists($attachment->path);
});

test('admin lesson create stores attachments and allows download', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('lessons-learned.view');

    $file = UploadedFile::fake()->image('bay.jpg');

    Livewire::actingAs($admin)
        ->test(LessonsLearned::class)
        ->call('openCreate')
        ->set('formReporterName', 'Admin User')
        ->set('formWorkedWell', 'Radios helped')
        ->set('attachments', [$file])
        ->call('save')
        ->assertHasNoErrors();

    $attachment = LessonLearnedAttachment::query()->first();
    expect($attachment)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.lessons-learned.attachments.download', $attachment))
        ->assertOk();
});

test('deleting a lesson removes attachment files from disk', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('lessons-learned.view');

    $lesson = LessonLearnedModel::query()->create([
        'source' => LessonLearnedModel::SOURCE_ADMIN,
        'created_by_user_id' => $admin->id,
        'reporter_name' => 'Admin',
        'category' => LessonLearnedModel::CATEGORY_PARKING,
        'convention_day' => 'all_days',
        'worked_well' => 'Ok',
    ]);

    Storage::disk('local')->put('lessons-learned/'.$lesson->id.'/demo.txt', 'hello');

    $attachment = LessonLearnedAttachment::query()->create([
        'lesson_learned_id' => $lesson->id,
        'disk' => 'local',
        'path' => 'lessons-learned/'.$lesson->id.'/demo.txt',
        'original_name' => 'demo.txt',
        'mime_type' => 'text/plain',
        'size_bytes' => 5,
        'kind' => LessonLearnedAttachment::KIND_FILE,
    ]);

    Livewire::actingAs($admin)
        ->test(LessonsLearned::class)
        ->call('delete', $lesson->id);

    expect(LessonLearnedModel::query()->find($lesson->id))->toBeNull()
        ->and(LessonLearnedAttachment::query()->find($attachment->id))->toBeNull();

    Storage::disk('local')->assertMissing($attachment->path);
});
