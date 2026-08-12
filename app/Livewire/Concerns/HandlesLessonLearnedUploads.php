<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Actions\LessonsLearned\StoreLessonLearnedAttachments;
use App\Models\LessonLearned;
use App\Models\LessonLearnedAttachment;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait HandlesLessonLearnedUploads
{
    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $voiceNotes = [];

    /**
     * @return array<string, mixed>
     */
    protected function lessonUploadValidationRules(string $attachmentsKey = 'attachments', string $voiceNotesKey = 'voiceNotes'): array
    {
        $maxFiles = (int) config('lessons-learned.max_upload_files', 10);
        $maxKb = (int) config('lessons-learned.max_upload_kilobytes', 20480);
        $mimes = implode(',', config('lessons-learned.allowed_mimes', []));

        return [
            $attachmentsKey => "nullable|array|max:{$maxFiles}",
            "{$attachmentsKey}.*" => "file|max:{$maxKb}|mimes:{$mimes}",
            $voiceNotesKey => "nullable|array|max:{$maxFiles}",
            "{$voiceNotesKey}.*" => "file|max:{$maxKb}|mimes:{$mimes}",
        ];
    }

    /**
     * @param  array<int, TemporaryUploadedFile|UploadedFile|mixed>  $attachments
     * @param  array<int, TemporaryUploadedFile|UploadedFile|mixed>  $voiceNotes
     */
    protected function storeLessonUploads(
        LessonLearned $lesson,
        array $attachments,
        array $voiceNotes,
        StoreLessonLearnedAttachments $action,
    ): void {
        $action->execute(
            $lesson,
            array_values(array_filter($attachments, fn ($file) => $file instanceof UploadedFile)),
            LessonLearnedAttachment::KIND_FILE,
        );

        $action->execute(
            $lesson,
            array_values(array_filter($voiceNotes, fn ($file) => $file instanceof UploadedFile)),
            LessonLearnedAttachment::KIND_VOICE_NOTE,
        );
    }

    protected function resetLessonUploads(): void
    {
        $this->attachments = [];
        $this->voiceNotes = [];
    }
}
