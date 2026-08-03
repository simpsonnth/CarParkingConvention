<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Congregation;
use App\Models\ParkingRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Throwable;

class MasterPassPdfGenerator
{
    /**
     * @param  list<int>  $registrationIds
     * @return list<array{filename: string, content: string, registration: ParkingRegistration}>
     */
    public function generateForIds(array $registrationIds): array
    {
        if ($registrationIds === []) {
            throw new \InvalidArgumentException('At least one registration ID is required.');
        }

        $registrations = ParkingRegistration::query()
            ->with('carPark')
            ->whereIn('id', $registrationIds)
            ->orderBy('id')
            ->get();

        if ($registrations->isEmpty()) {
            throw new RuntimeException('No registrations found for the given IDs.');
        }

        return $this->generateForRegistrations($registrations);
    }

    /**
     * @param  Collection<int, ParkingRegistration>  $registrations
     * @return list<array{filename: string, content: string, registration: ParkingRegistration}>
     */
    public function generateForRegistrations(Collection $registrations): array
    {
        $usedNames = [];
        $attachments = [];
        $skipped = [];

        foreach ($registrations as $registration) {
            try {
                $attachments[] = [
                    'filename' => $this->uniquePersonFilename($registration, $usedNames),
                    'content' => $this->generatePdf($registration),
                    'registration' => $registration,
                ];
            } catch (Throwable $exception) {
                Log::warning('Master pass PDF skipped for registration', [
                    'registration_id' => $registration->id,
                    'name' => $registration->name,
                    'error' => $exception->getMessage(),
                ]);
                $skipped[] = $registration->name.' (#'.$registration->id.')';
            }
        }

        if ($attachments === []) {
            $hint = $skipped !== []
                ? ' Skipped: '.implode(', ', array_slice($skipped, 0, 5)).(count($skipped) > 5 ? '…' : '')
                : '';

            throw new RuntimeException('No valid car park tickets could be generated.'.$hint);
        }

        return $attachments;
    }

    public function generatePdf(ParkingRegistration $registration): string
    {
        [$congregation, $effectiveCarPark] = $this->resolvePassContext($registration);

        // Prefer Chrome (Browsershot) for print-accurate layout; fall back to DomPDF when
        // Chrome/system libraries are unavailable (common on lean production VPS images).
        try {
            return $this->generatePdfWithChrome($registration, $congregation, $effectiveCarPark);
        } catch (Throwable $chromeException) {
            Log::warning('Chrome PDF failed; falling back to DomPDF', [
                'registration_id' => $registration->id,
                'error' => $chromeException->getMessage(),
            ]);

            try {
                return $this->generatePdfWithDomPdf($registration, $congregation, $effectiveCarPark);
            } catch (Throwable $domPdfException) {
                throw new RuntimeException(
                    'Could not render car park ticket PDF (Chrome and DomPDF both failed). Chrome: '
                    .$chromeException->getMessage().' DomPDF: '.$domPdfException->getMessage(),
                    0,
                    $domPdfException
                );
            }
        }
    }

    /**
     * @return array{0: ?Congregation, 1: mixed}
     */
    public function resolvePassContext(ParkingRegistration $registration): array
    {
        if ($registration->is_circuit_overseer) {
            if ($registration->carPark === null && $registration->car_park_id === null) {
                throw new RuntimeException('Circuit Overseer registration #'.$registration->id.' has no car park assigned.');
            }

            $registration->loadMissing('carPark');

            return [null, $registration->carPark];
        }

        $congregation = Congregation::query()
            ->with('carPark')
            ->where('name', $registration->congregation)
            ->first();

        if (! $congregation) {
            throw new RuntimeException('Congregation not found for registration #'.$registration->id.'.');
        }

        $effectiveCarPark = $registration->carPark ?? $congregation->carPark;

        return [$congregation, $effectiveCarPark];
    }

    /**
     * @param  array<string, true>  $usedNames
     */
    public function uniquePersonFilename(ParkingRegistration $registration, array &$usedNames): string
    {
        $base = trim((string) preg_replace('/[\/\\\\:\*\?"<>\|\x00-\x1F]/', '', $registration->name));
        if ($base === '') {
            $base = 'Pass-'.$registration->id;
        }

        $base = mb_substr($base, 0, 100);
        $filename = $base.'.pdf';
        $i = 2;

        while (isset($usedNames[mb_strtolower($filename)])) {
            $filename = $base.' ('.$i.').pdf';
            $i++;
        }

        $usedNames[mb_strtolower($filename)] = true;

        return $filename;
    }

    protected function generatePdfWithChrome(
        ParkingRegistration $registration,
        ?Congregation $congregation,
        mixed $effectiveCarPark
    ): string {
        $html = view('admin.print-pass', [
            'congregation' => $congregation,
            'registration' => $registration,
            'effectiveCarPark' => $effectiveCarPark,
            'forChromePdf' => true,
        ])->render();

        $html = $this->embedLocalAssetsAsDataUris($html);

        return $this->browsershot($html)->pdf();
    }

    protected function generatePdfWithDomPdf(
        ParkingRegistration $registration,
        ?Congregation $congregation,
        mixed $effectiveCarPark
    ): string {
        $html = view('admin.print-pass', [
            'congregation' => $congregation,
            'registration' => $registration,
            'effectiveCarPark' => $effectiveCarPark,
            'forPdf' => true,
        ])->render();

        $html = $this->embedLocalAssetsAsDataUris($html);

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->output();
    }

    protected function browsershot(string $html): Browsershot
    {
        $shot = Browsershot::html($html)
            ->noSandbox()
            ->emulateMedia('print')
            ->showBackground()
            ->landscape()
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->discardConsoleMessages()
            // Keep emailed PDFs smaller without changing print layout much.
            ->setOption('printBackground', true)
            ->preferCSSPageSize();

        $nodeModules = base_path('node_modules');
        if (is_dir($nodeModules)) {
            $shot->setNodeModulePath($nodeModules);
        }

        $chromePath = (string) config('services.chrome.binary', '');
        if ($chromePath !== '' && is_executable($chromePath)) {
            $shot->setChromePath($chromePath);
        } else {
            // Prefer Puppeteer's bundled Chromium when system Chrome is missing.
            $puppeteerChrome = $this->puppeteerChromePath();
            if ($puppeteerChrome !== null) {
                $shot->setChromePath($puppeteerChrome);
            }
        }

        return $shot;
    }

    protected function puppeteerChromePath(): ?string
    {
        $candidates = [
            getenv('HOME') !== false
                ? rtrim((string) getenv('HOME'), '/').'/.cache/puppeteer/chrome'
                : null,
            '/home/ploi/.cache/puppeteer/chrome',
        ];

        foreach ($candidates as $root) {
            if ($root === null || ! is_dir($root)) {
                continue;
            }

            $matches = glob($root.'/linux-*/chrome-linux64/chrome') ?: [];
            rsort($matches);
            foreach ($matches as $path) {
                if (is_executable($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Embed /storage/... images as data URIs so Chrome/DomPDF do not need the HTTP server.
     */
    protected function embedLocalAssetsAsDataUris(string $html): string
    {
        $replaced = preg_replace_callback(
            '#\bsrc=(["\'])((?:https?://[^"\']+)?/?storage/[^"\']+)\1#i',
            function (array $matches): string {
                $src = $matches[2];
                $path = parse_url($src, PHP_URL_PATH) ?: $src;
                $path = '/'.ltrim((string) $path, '/');

                if (! str_starts_with($path, '/storage/')) {
                    return $matches[0];
                }

                $absolute = public_path($path);
                if (! is_file($absolute)) {
                    $relative = substr($path, strlen('/storage/'));
                    $absolute = storage_path('app/public/'.$relative);
                }

                if (! is_file($absolute)) {
                    return $matches[0];
                }

                $mime = mime_content_type($absolute) ?: 'application/octet-stream';
                $data = base64_encode((string) file_get_contents($absolute));

                return 'src='.$matches[1].'data:'.$mime.';base64,'.$data.$matches[1];
            },
            $html
        );

        return is_string($replaced) ? $replaced : $html;
    }
}
