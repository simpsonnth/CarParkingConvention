<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class ExportToolboxTalkPdf
{
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

            $slides[] = [
                'type' => $type,
                'title' => (string) $slide['title'],
                'body' => (string) ($slide['body'] ?? ''),
                'section_label' => (string) ($slide['section_label'] ?? ''),
                'background' => $type === 'cover' ? $this->coverDataUri($parkId) : null,
                'lines' => $type === 'content' ? $this->normalizeBodyLines((string) ($slide['body'] ?? '')) : [],
            ];
        }

        $html = view('admin.toolbox-talk-pdf', [
            'slides' => $slides,
            'talkDate' => $talkDate,
        ])->render();

        $content = Pdf::loadHTML($html)
            // Widescreen 16:9 in points (matches PPTX frame).
            ->setPaper([0.0, 0.0, 960.0, 540.0])
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('dpi', 96)
            ->output();

        return [
            'filename' => 'toolbox-talk-'.$talkDate.'-full.pdf',
            'content' => $content,
        ];
    }

    private function coverDataUri(?int $carParkId): ?string
    {
        $path = $this->resolveCover->absolutePath($carParkId);
        if (! is_file($path)) {
            return null;
        }

        $bytes = file_get_contents($path);
        if ($bytes === false) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
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
