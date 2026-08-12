<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\LessonLearnedAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LessonLearnedAttachmentDownloadController
{
    public function __invoke(Request $request, LessonLearnedAttachment $attachment): StreamedResponse
    {
        abort_unless($request->user()?->can('lessons-learned.view'), 403);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
