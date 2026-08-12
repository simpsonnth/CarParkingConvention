<?php

declare(strict_types=1);

namespace App\Actions\LessonsLearned;

use App\Models\LessonLearned;
use App\Models\LessonLearnedAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class StoreLessonLearnedAttachments
{
    /**
     * @param  list<UploadedFile>  $files
     */
    public function execute(LessonLearned $lesson, array $files, string $kind): void
    {
        if ($files === []) {
            return;
        }

        $disk = (string) config('lessons-learned.disk', 'local');

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $originalName = $file->getClientOriginalName() ?: ($kind === LessonLearnedAttachment::KIND_VOICE_NOTE
                ? 'voice-note.webm'
                : 'attachment.bin');

            $basename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $extension = strtolower((string) $file->getClientOriginalExtension());
            if ($extension === '') {
                $extension = $kind === LessonLearnedAttachment::KIND_VOICE_NOTE ? 'webm' : 'bin';
            }

            $path = sprintf(
                'lessons-learned/%d/%s-%s.%s',
                $lesson->id,
                (string) Str::uuid(),
                $basename !== '' ? $basename : $kind,
                $extension,
            );

            Storage::disk($disk)->put(
                $path,
                file_get_contents($file->getRealPath()) ?: '',
                ['visibility' => 'private'],
            );

            $lesson->attachments()->create([
                'disk' => $disk,
                'path' => $path,
                'original_name' => $originalName,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => (int) ($file->getSize() ?: 0),
                'kind' => $kind,
            ]);
        }
    }
}
