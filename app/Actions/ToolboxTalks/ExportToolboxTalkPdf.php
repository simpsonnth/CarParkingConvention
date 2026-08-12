<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class ExportToolboxTalkPdf
{
    private const SLIDE_WIDTH = 960;

    private const SLIDE_HEIGHT = 540;

    public function __construct(
        private readonly BuildToolboxTalkDeck $buildDeck,
        private readonly ResolveToolboxTalkCover $resolveCover,
    ) {}

    /**
     * @return array{filename: string, content: string}
     */
    public function handle(Carbon|string $date): array
    {
        $talkDate = Carbon::parse($date)->toDateString();
        $deck = $this->buildDeck->handleFull($talkDate);

        if ($deck === []) {
            $deck = [[
                'type' => 'content',
                'title' => __('toolbox_talks.present_empty_title'),
                'body' => __('toolbox_talks.present_empty_body'),
                'section' => 'core',
                'section_label' => __('toolbox_talks.section_core'),
            ]];
        }

        $slides = [];
        $slides[] = [
            'type' => 'title',
            'title' => __('toolbox_talks.pptx_title'),
            'body' => __('toolbox_talks.pptx_full_subtitle', ['date' => $talkDate]),
            'section_label' => __('toolbox_talks.section_core'),
            'background' => $this->coverDataUri(null),
        ];

        foreach ($deck as $slide) {
            $parkId = isset($slide['car_park_id']) ? (int) $slide['car_park_id'] : null;
            $type = (string) ($slide['type'] ?? 'content');
            $isJhaCover = ($slide['cover'] ?? null) === 'jha' || ($slide['section'] ?? null) === 'jha';

            $slides[] = [
                'type' => $type,
                'title' => (string) $slide['title'],
                'body' => (string) ($slide['body'] ?? ''),
                'section_label' => (string) ($slide['section_label'] ?? ''),
                'is_jha_cover' => $isJhaCover,
                'background' => $type === 'cover'
                    ? $this->coverDataUri($isJhaCover ? null : $parkId, $isJhaCover ? 'jha' : null)
                    : null,
                'lines' => $type === 'content' ? $this->normalizeBodyLines((string) ($slide['body'] ?? '')) : [],
            ];
        }

        $html = view('admin.toolbox-talk-pdf', [
            'slides' => $slides,
            'talkDate' => $talkDate,
        ])->render();

        $content = Pdf::loadHTML($html)
            ->setPaper([0.0, 0.0, (float) self::SLIDE_WIDTH, (float) self::SLIDE_HEIGHT])
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('dpi', 96)
            ->output();

        return [
            'filename' => 'toolbox-talk-'.$talkDate.'-full.pdf',
            'content' => $content,
        ];
    }

    /**
     * Build a 16:9 cover image (cover-cropped + darkened) as a data URI for DomPDF.
     */
    private function coverDataUri(?int $carParkId, ?string $cover = null): ?string
    {
        $path = $cover === 'jha'
            ? $this->resolveCover->jhaAbsolutePath()
            : $this->resolveCover->absolutePath($carParkId);
        if (! is_file($path)) {
            return null;
        }

        $source = @imagecreatefromstring((string) file_get_contents($path));
        if ($source === false) {
            return null;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);

            return null;
        }

        $dstW = self::SLIDE_WIDTH;
        $dstH = self::SLIDE_HEIGHT;
        $dst = imagecreatetruecolor($dstW, $dstH);
        if ($dst === false) {
            imagedestroy($source);

            return null;
        }

        // Cover-crop into 16:9 so the photo fills the slide (matches PPTX background).
        $scale = max($dstW / $srcW, $dstH / $srcH);
        $cropW = (int) round($dstW / $scale);
        $cropH = (int) round($dstH / $scale);
        $srcX = (int) max(0, floor(($srcW - $cropW) / 2));
        $srcY = (int) max(0, floor(($srcH - $cropH) / 2));

        imagecopyresampled(
            $dst,
            $source,
            0,
            0,
            $srcX,
            $srcY,
            $dstW,
            $dstH,
            $cropW,
            $cropH,
        );

        // Darken for readable white text over the photo.
        imagefilter($dst, IMG_FILTER_BRIGHTNESS, -45);
        imagefilter($dst, IMG_FILTER_CONTRAST, -8);

        ob_start();
        imagejpeg($dst, null, 82);
        $bytes = ob_get_clean();
        imagedestroy($source);
        imagedestroy($dst);

        if (! is_string($bytes) || $bytes === '') {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($bytes);
    }

    /**
     * @return list<array{bullet: bool, text: string}>
     */
    private function normalizeBodyLines(string $bodyText): array
    {
        $raw = preg_split("/\R/u", $bodyText) ?: [];
        $lines = [];

        foreach ($raw as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $isBullet = (bool) preg_match('/^(?:[•\-\*\x{00B7}]|\d+[\.\)])(?:\s+|$)/u', $line);
            $text = preg_replace('/^(?:[•\-\*\x{00B7}]|\d+[\.\)])(?:\s+|$)/u', '', $line) ?? $line;
            $text = trim($text);
            if ($text === '') {
                continue;
            }

            $lines[] = [
                'bullet' => $isBullet,
                'text' => $text,
            ];
        }

        if (count($lines) > 1) {
            $bulletCount = count(array_filter($lines, fn (array $l): bool => $l['bullet']));
            if ($bulletCount === 0) {
                foreach ($lines as $i => $line) {
                    if ($i === 0) {
                        continue;
                    }
                    $lines[$i]['bullet'] = true;
                }
            }
        }

        return $lines;
    }
}
