<?php

declare(strict_types=1);

namespace App\Actions\LessonsLearned;

use App\Models\LessonLearned;
use App\Models\LessonLearnedAttachment;
use App\Support\ConventionDay;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ExportLessonsLearnedPdf
{
    /**
     * @return array{filename: string, content: string}
     */
    public function handle(): array
    {
        $lessons = LessonLearned::query()
            ->with('attachments')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LessonLearned $lesson): array => $this->presentLesson($lesson));

        $html = view('admin.lessons-learned-pdf', [
            'lessons' => $lessons,
            'exportedAt' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'total' => $lessons->count(),
        ])->render();

        $content = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->output();

        return [
            'filename' => 'lessons-learned-'.now()->format('Y-m-d-His').'.pdf',
            'content' => $content,
        ];
    }

    /**
     * @return array{
     *     submitted: string,
     *     source: string,
     *     category: string,
     *     day: string,
     *     title: string,
     *     reporter: string,
     *     worked_well: string,
     *     didnt_work_well: string,
     *     images: list<array{name: string, data_uri: string|null, url: string}>,
     *     voice_notes: list<array{name: string, url: string}>,
     *     other_files: list<array{name: string, url: string}>
     * }
     */
    private function presentLesson(LessonLearned $lesson): array
    {
        $images = [];
        $voiceNotes = [];
        $otherFiles = [];

        foreach ($lesson->attachments as $attachment) {
            if ($attachment->isVoiceNote()) {
                $voiceNotes[] = [
                    'name' => $attachment->original_name,
                    'url' => $this->attachmentUrl($attachment, listen: true),
                ];

                continue;
            }

            if ($this->isImage($attachment)) {
                $images[] = [
                    'name' => $attachment->original_name,
                    'data_uri' => $this->imageDataUri($attachment),
                    'url' => $this->attachmentUrl($attachment, listen: false),
                ];

                continue;
            }

            $otherFiles[] = [
                'name' => $attachment->original_name,
                'url' => $this->attachmentUrl($attachment, listen: false),
            ];
        }

        return [
            'submitted' => $lesson->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '',
            'source' => $lesson->source === LessonLearned::SOURCE_ADMIN ? 'Admin' : 'Public',
            'category' => ucfirst((string) $lesson->category),
            'day' => ConventionDay::label($lesson->convention_day ?? ConventionDay::ALL_DAYS),
            'title' => (string) ($lesson->title ?? ''),
            'reporter' => (string) $lesson->reporter_name,
            'worked_well' => (string) ($lesson->worked_well ?? ''),
            'didnt_work_well' => (string) ($lesson->didnt_work_well ?? ''),
            'images' => $images,
            'voice_notes' => $voiceNotes,
            'other_files' => $otherFiles,
        ];
    }

    private function isImage(LessonLearnedAttachment $attachment): bool
    {
        $mime = strtolower((string) $attachment->mime_type);
        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        $name = strtolower((string) $attachment->original_name);

        return (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp)$/', $name);
    }

    private function attachmentUrl(LessonLearnedAttachment $attachment, bool $listen): string
    {
        try {
            return Storage::disk($attachment->disk)->temporaryUrl(
                $attachment->path,
                now()->addDays(7),
            );
        } catch (\Throwable) {
            // Local disks (and some configs) cannot mint temporary URLs.
        }

        if ($listen && $attachment->isVoiceNote()) {
            return route('admin.lessons-learned.attachments.stream', $attachment);
        }

        return route('admin.lessons-learned.attachments.download', $attachment);
    }

    private function imageDataUri(LessonLearnedAttachment $attachment): ?string
    {
        try {
            $contents = Storage::disk($attachment->disk)->get($attachment->path);
            if ($contents === null || $contents === '') {
                return null;
            }

            $mime = strtolower((string) $attachment->mime_type);
            if ($mime === '' || ! str_starts_with($mime, 'image/')) {
                $extension = strtolower(pathinfo((string) $attachment->original_name, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'bmp' => 'image/bmp',
                    default => 'image/jpeg',
                };
            }

            return 'data:'.$mime.';base64,'.base64_encode($contents);
        } catch (\Throwable) {
            return null;
        }
    }
}
