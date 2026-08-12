<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\LessonLearnedAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LessonLearnedAttachmentStreamController
{
    public function __invoke(Request $request, LessonLearnedAttachment $attachment): StreamedResponse
    {
        abort_unless($request->user()?->can('lessons-learned.view'), 403);
        abort_unless($attachment->isVoiceNote(), 404);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        $mime = $attachment->mime_type ?: 'audio/webm';

        return $disk->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"',
            ],
        );
    }
}
