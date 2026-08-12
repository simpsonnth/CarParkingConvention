<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LessonLearnedAttachment extends Model
{
    public const KIND_FILE = 'file';

    public const KIND_VOICE_NOTE = 'voice_note';

    protected $fillable = [
        'lesson_learned_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'kind',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function lessonLearned(): BelongsTo
    {
        return $this->belongsTo(LessonLearned::class);
    }

    public function isVoiceNote(): bool
    {
        return $this->kind === self::KIND_VOICE_NOTE;
    }

    public function deleteFromDisk(): void
    {
        try {
            Storage::disk($this->disk)->delete($this->path);
        } catch (\Throwable) {
            // Best-effort cleanup; DB row removal should still proceed.
        }
    }
}
