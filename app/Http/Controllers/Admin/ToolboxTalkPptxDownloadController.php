<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ToolboxTalks\ExportToolboxTalkPowerpoint;
use App\Models\CarPark;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ToolboxTalkPptxDownloadController
{
    public function __invoke(
        Request $request,
        string $date,
        ExportToolboxTalkPowerpoint $export,
        ?CarPark $carPark = null,
    ): StreamedResponse {
        abort_unless($request->user()?->can('toolbox-talks.view'), 403);

        $result = $export->handle($date, $carPark?->id);

        return response()->streamDownload(
            static function () use ($result): void {
                echo $result['content'];
            },
            $result['filename'],
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ]
        );
    }
}
