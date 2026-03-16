<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\ParkingRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use ZipArchive;

class MasterPassZipService
{
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
        if (empty($registrationIds)) {
            throw new \InvalidArgumentException('At least one registration ID is required.');
        }

        if (! extension_loaded('gd')) {
            throw new \RuntimeException(
                'The PHP GD extension is required to generate PDFs with images (e.g. QR codes). ' .
                'Install it and restart your web server — e.g. on Ubuntu/Debian: sudo apt install php-gd'
            );
        }

        $registrations = ParkingRegistration::query()
            ->with('carPark')
            ->whereIn('id', $registrationIds)
            ->orderBy('id')
            ->get();

        if ($registrations->isEmpty()) {
            throw new \RuntimeException('No registrations found for the given IDs.');
        }

        $zipPath = storage_path('app/temp/master-passes-' . Str::random(16) . '.zip');
        $dir = dirname($zipPath);
        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0755, true)) {
                throw new \RuntimeException('Could not create temp directory for ZIP.');
            }
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create ZIP file.');
        }

        try {
            foreach ($registrations as $registration) {
                $congregation = Congregation::where('name', $registration->congregation)->first();
                if (! $congregation) {
                    continue;
                }

                $pdfContent = $this->generatePdfForRegistration($registration, $congregation);
                $filename = $this->safePdfFilename($registration, $congregation);
                $zip->addFromString($filename, $pdfContent);
            }

            if ($zip->numFiles === 0) {
                $zip->close();
                @unlink($zipPath);
                throw new \RuntimeException('No valid passes could be generated (congregation not found for all selected).');
            }
        } finally {
            $zip->close();
        }

        $downloadName = 'master-passes-' . now()->format('Y-m-d-His') . '.zip';

        return [$zipPath, $downloadName];
    }

    protected function generatePdfForRegistration(ParkingRegistration $registration, Congregation $congregation): string
    {
        $html = view('admin.print-pass', [
            'congregation' => $congregation,
            'registration' => $registration,
            'forPdf' => true,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setWarnings(false)
            ->setOption('enable_remote', true)
            ->output();
    }

    /** Build a safe, unique PDF filename for the ZIP entry. */
    protected function safePdfFilename(ParkingRegistration $registration, Congregation $congregation): string
    {
        $congSlug = Str::slug($congregation->name, '-');
        $nameSlug = Str::slug($registration->name, '-');
        $base = "Master-Pass-{$registration->id}-{$congSlug}-{$nameSlug}";
        $base = preg_replace('/[^A-Za-z0-9\-_]/', '-', $base) ?: 'pass';
        $base = Str::limit($base, 120);

        return $base . '.pdf';
    }
}
