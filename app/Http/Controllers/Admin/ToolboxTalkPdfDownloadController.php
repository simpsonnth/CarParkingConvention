<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ToolboxTalks\ExportToolboxTalkPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ToolboxTalkPdfDownloadController
{
    public function __invoke(
        Request $request,
        string $date,
        ExportToolboxTalkPdf $export,
    ): StreamedResponse {
        abort_unless($request->user()?->can('toolbox-talks.view'), 403);

        $result = $export->handle($date);

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
