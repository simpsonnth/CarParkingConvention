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

test('admin can stream voice notes for in-page playback', function () {
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

    Storage::disk('local')->put('lessons-learned/'.$lesson->id.'/voice.webm', 'fake-audio');

    $attachment = LessonLearnedAttachment::query()->create([
        'lesson_learned_id' => $lesson->id,
        'disk' => 'local',
        'path' => 'lessons-learned/'.$lesson->id.'/voice.webm',
        'original_name' => 'voice.webm',
        'mime_type' => 'audio/webm',
        'size_bytes' => 10,
        'kind' => LessonLearnedAttachment::KIND_VOICE_NOTE,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.lessons-learned.attachments.stream', $attachment))
        ->assertOk()
        ->assertHeader('content-type', 'audio/webm');

    $fileAttachment = LessonLearnedAttachment::query()->create([
        'lesson_learned_id' => $lesson->id,
        'disk' => 'local',
        'path' => 'lessons-learned/'.$lesson->id.'/notes.pdf',
        'original_name' => 'notes.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 10,
        'kind' => LessonLearnedAttachment::KIND_FILE,
    ]);

    Storage::disk('local')->put($fileAttachment->path, 'pdf');

    $this->actingAs($admin)
        ->get(route('admin.lessons-learned.attachments.stream', $fileAttachment))
        ->assertNotFound();
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

test('guest cannot export lessons learned', function () {
    $this->get(route('admin.lessons-learned.export'))
        ->assertRedirect();
});

test('admin without permission cannot export lessons learned', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.lessons-learned.export'))
        ->assertForbidden();
});

test('admin can export lessons learned with image and voice note links', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('lessons-learned.view');

    $lesson = LessonLearnedModel::query()->create([
        'source' => LessonLearnedModel::SOURCE_PUBLIC,
        'reporter_name' => 'Export Reporter',
        'category' => LessonLearnedModel::CATEGORY_OPERATIONS,
        'convention_day' => 'all_days',
        'title' => 'Export title',
        'worked_well' => 'Radios',
        'didnt_work_well' => 'Queues',
    ]);

    $imageBinary = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    );
    Storage::disk('local')->put('lessons-learned/'.$lesson->id.'/bay.png', $imageBinary);

    LessonLearnedAttachment::query()->create([
        'lesson_learned_id' => $lesson->id,
        'disk' => 'local',
        'path' => 'lessons-learned/'.$lesson->id.'/bay.png',
        'original_name' => 'bay.png',
        'mime_type' => 'image/png',
        'size_bytes' => strlen($imageBinary),
        'kind' => LessonLearnedAttachment::KIND_FILE,
    ]);

    Storage::disk('local')->put('lessons-learned/'.$lesson->id.'/voice.webm', 'fake-audio');

    LessonLearnedAttachment::query()->create([
        'lesson_learned_id' => $lesson->id,
        'disk' => 'local',
        'path' => 'lessons-learned/'.$lesson->id.'/voice.webm',
        'original_name' => 'voice.webm',
        'mime_type' => 'audio/webm',
        'size_bytes' => 10,
        'kind' => LessonLearnedAttachment::KIND_VOICE_NOTE,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.lessons-learned.export'))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $base = $response->baseResponse;
    expect($base)->toBeInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class);

    $temp = tempnam(sys_get_temp_dir(), 'll-export-');
    expect($temp)->not->toBeFalse();
    copy($base->getFile()->getPathname(), $temp);

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($temp);
    @unlink($temp);

    $sheet = $spreadsheet->getActiveSheet();
    expect($sheet->getCell('A1')->getValue())->toBe('Submitted')
        ->and($sheet->getCell('K1')->getValue())->toBe('Voice notes')
        ->and($sheet->getCell('F2')->getValue())->toBe('Export Reporter')
        ->and((string) $sheet->getCell('J2')->getValue())->toContain('bay.png')
        ->and((string) $sheet->getCell('K2')->getValue())->toContain('Listen: voice.webm');

    $voiceHyperlink = $sheet->getCell('K2')->getHyperlink()->getUrl();
    expect($voiceHyperlink)->not->toBeEmpty()
        ->and(
            str_contains($voiceHyperlink, '/stream')
            || str_contains($voiceHyperlink, 'voice.webm')
        )->toBeTrue();

    $imageHyperlink = $sheet->getCell('J2')->getHyperlink()->getUrl();
    expect($imageHyperlink)->not->toBeEmpty()
        ->and(
            str_contains($imageHyperlink, '/download')
            || str_contains($imageHyperlink, 'bay.png')
        )->toBeTrue();

    expect($sheet->getDrawingCollection()->count())->toBeGreaterThan(0);
});
