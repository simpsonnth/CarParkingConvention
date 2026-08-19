<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\LessonsLearned\ExportLessonsLearnedPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LessonsLearnedPdfDownloadController
{
    public function __invoke(Request $request, ExportLessonsLearnedPdf $export): StreamedResponse
    {
        abort_unless($request->user()?->can('lessons-learned.view'), 403);

        $result = $export->handle();

        return response()->streamDownload(
            static function () use ($result): void {
                echo $result['content'];
            },
            $result['filename'],
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }
}
