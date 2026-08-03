<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ParkingRegistration;
use Illuminate\Support\Str;
use ZipArchive;

class MasterPassZipService
{
    public function __construct(
        protected MasterPassPdfGenerator $pdfGenerator,
    ) {}

    /**
     * Build a ZIP file containing one PDF master pass per selected registration.
     * Returns the path to the temporary ZIP file (caller must delete after sending).
     *
     * @param  array<int>  $registrationIds
     * @return array{0: string, 1: string} [path to zip file, suggested download filename]
     *
     * @throws \RuntimeException
     */
    public function buildZip(array $registrationIds): array
    {
        @set_time_limit(300);
        ini_set('max_execution_time', '300');

        if (empty($registrationIds)) {
            throw new \InvalidArgumentException('At least one registration ID is required.');
        }

        $registrations = ParkingRegistration::query()
            ->with('carPark')
            ->whereIn('id', $registrationIds)
            ->orderBy('id')
            ->get();

        if ($registrations->isEmpty()) {
            throw new \RuntimeException('No registrations found for the given IDs.');
        }

        $attachments = $this->pdfGenerator->generateForRegistrations($registrations);

        $zipPath = storage_path('app/temp/master-passes-'.Str::random(16).'.zip');
        $dir = dirname($zipPath);
        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0755, true)) {
                throw new \RuntimeException('Could not create temp directory for ZIP.');
            }
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create ZIP file.');
        }

        try {
            foreach ($attachments as $attachment) {
                $zip->addFromString($attachment['filename'], $attachment['content']);
            }
        } finally {
            $zip->close();
        }

        $downloadName = 'master-passes-'.now()->format('Y-m-d-His').'.zip';

        return [$zipPath, $downloadName];
    }
}
