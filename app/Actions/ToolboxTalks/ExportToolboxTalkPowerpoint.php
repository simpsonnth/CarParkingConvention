<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use Illuminate\Support\Carbon;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\RichText\Paragraph;
use PhpOffice\PhpPresentation\Slide\Background\Color as BackgroundColor;
use PhpOffice\PhpPresentation\Slide\Background\Image as BackgroundImage;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Style\Color;

class ExportToolboxTalkPowerpoint
{
    /** Layout coordinates assume 16:9 at 96dpi (~960×540). */
    private const MARGIN_X = 48;

    private const CONTENT_WIDTH = 864;

    private const SLIDE_HEIGHT = 540;

    private const BOTTOM_MARGIN = 28;

    public function __construct(
        private readonly BuildToolboxTalkDeck $buildDeck,
        private readonly ResolveToolboxTalkCover $resolveCover,
    ) {}

    /**
     * Full briefing: title + Core + every car park (cover dividers + add-ons).
     *
     * @return array{filename: string, content: string}
     */
    public function handle(Carbon|string $date): array
    {
        $talkDate = Carbon::parse($date)->toDateString();
        $deck = $this->buildDeck->handleFull($talkDate);

        $presentation = new PhpPresentation();
        $presentation->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);
        $presentation->removeSlideByIndex(0);

        $this->addTitleSlide($presentation, $talkDate);

        foreach ($deck as $slideData) {
            if (($slideData['type'] ?? 'content') === 'cover') {
                $this->addParkCoverSlide($presentation, $slideData);

                continue;
            }

            $this->addContentSlide($presentation, $slideData);
        }

        if ($deck === []) {
            $this->addContentSlide($presentation, [
                'type' => 'content',
                'title' => __('toolbox_talks.present_empty_title'),
                'body' => __('toolbox_talks.present_empty_body'),
                'section_label' => __('toolbox_talks.section_core'),
            ]);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'toolbox-talk-');
        if ($tmp === false) {
            throw new \RuntimeException('Unable to create temporary file for PowerPoint export.');
        }

        $path = $tmp.'.pptx';
        @unlink($tmp);

        $writer = IOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save($path);

        $content = file_get_contents($path);
        @unlink($path);

        if ($content === false) {
            throw new \RuntimeException('Unable to read generated PowerPoint file.');
        }

        return [
            'filename' => 'toolbox-talk-'.$talkDate.'-full.pptx',
            'content' => $content,
        ];
    }

    private function addTitleSlide(PhpPresentation $presentation, string $talkDate): void
    {
        $slide = $presentation->createSlide();
        $this->applyCoverBackground($slide, null);

        $kicker = $slide->createRichTextShape()
            ->setHeight(36)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY(140);
        $this->lockTextBox($kicker);
        $kicker->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $kickerRun = $kicker->createTextRun(mb_strtoupper(__('toolbox_talks.section_core')));
        $kickerRun->getFont()->setBold(true)->setSize(14)->setColor(new Color('FF5EEAD4'));

        $title = $slide->createRichTextShape()
            ->setHeight(120)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY(190);
        $this->lockTextBox($title);
        $title->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $run = $title->createTextRun(__('toolbox_talks.pptx_title'));
        $run->getFont()->setBold(true)->setSize(36)->setColor(new Color('FFFFFFFF'));

        $sub = $slide->createRichTextShape()
            ->setHeight(80)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY(330);
        $this->lockTextBox($sub);
        $sub->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $subRun = $sub->createTextRun(__('toolbox_talks.pptx_full_subtitle', ['date' => $talkDate]));
        $subRun->getFont()->setSize(18)->setColor(new Color('FFCCFBF1'));
    }

    /**
     * @param  array{type?: string, title: string, body?: string, section_label?: string, car_park_id?: int|null, cover?: string|null}  $slideData
     */
    private function addParkCoverSlide(PhpPresentation $presentation, array $slideData): void
    {
        $slide = $presentation->createSlide();
        $isJha = ($slideData['cover'] ?? null) === 'jha' || ($slideData['section'] ?? null) === 'jha';
        $parkId = isset($slideData['car_park_id']) ? (int) $slideData['car_park_id'] : null;
        $this->applyCoverBackground($slide, $parkId, $isJha ? 'jha' : null);

        $kickerLabel = $isJha
            ? __('toolbox_talks.jha_cover_kicker')
            : __('toolbox_talks.park_cover_kicker');

        $kicker = $slide->createRichTextShape()
            ->setHeight(36)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY(150);
        $this->lockTextBox($kicker);
        $kicker->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $kickerRun = $kicker->createTextRun(mb_strtoupper($kickerLabel));
        $kickerColor = $isJha ? 'FFFACC15' : 'FF5EEAD4';
        $kickerRun->getFont()->setBold(true)->setSize(14)->setColor(new Color($kickerColor));

        $title = $slide->createRichTextShape()
            ->setHeight(140)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY(210);
        $this->lockTextBox($title);
        $title->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $titleRun = $title->createTextRun((string) $slideData['title']);
        $titleRun->getFont()->setBold(true)->setSize(40)->setColor(new Color('FFFFFFFF'));

        $body = trim((string) ($slideData['body'] ?? ''));
        if ($body === '') {
            return;
        }

        $sub = $slide->createRichTextShape()
            ->setHeight(70)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY(360);
        $this->lockTextBox($sub);
        $sub->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $subRun = $sub->createTextRun($body);
        $subRun->getFont()->setSize(20)->setColor(new Color('FFCCFBF1'));
    }

    /**
     * @param  array{type?: string, title: string, body: string, section_label?: string}  $slideData
     */
    private function addContentSlide(PhpPresentation $presentation, array $slideData): void
    {
        $slide = $presentation->createSlide();
        $this->applyDarkBackground($slide);

        $y = 28;
        $section = trim((string) ($slideData['section_label'] ?? ''));
        if ($section !== '') {
            $chip = $slide->createRichTextShape()
                ->setHeight(26)
                ->setWidth(self::CONTENT_WIDTH)
                ->setOffsetX(self::MARGIN_X)
                ->setOffsetY($y);
            $this->lockTextBox($chip);
            $chipRun = $chip->createTextRun(mb_strtoupper($section));
            $chipRun->getFont()->setBold(true)->setSize(12)->setColor(new Color('FF5EEAD4'));
            $y += 28;
        }

        $titleText = (string) $slideData['title'];
        $titleLines = $this->estimateWrappedLines($titleText, 26, indentChars: 0);
        $titleHeight = $titleLines >= 2 ? 70 : 44;

        $titleShape = $slide->createRichTextShape()
            ->setHeight($titleHeight)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY($y);
        $this->lockTextBox($titleShape);
        $titlePara = $titleShape->getActiveParagraph();
        $titlePara->setLineSpacing(110);
        $titlePara->setSpacingAfter(0);
        $titleRun = $titleShape->createTextRun($titleText);
        $titleRun->getFont()->setBold(true)->setSize(26)->setColor(new Color('FFFFFFFF'));
        $y += $titleHeight + 10;

        $bodyText = trim((string) ($slideData['body'] ?? ''));
        if ($bodyText === '') {
            return;
        }

        $bodyHeight = max(80, self::SLIDE_HEIGHT - $y - self::BOTTOM_MARGIN);
        $body = $slide->createRichTextShape()
            ->setHeight($bodyHeight)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY($y);
        $this->lockTextBox($body);

        $lines = $this->normalizeBodyLines($bodyText);
        $layout = $this->pickBodyLayout($lines, $bodyHeight);
        $firstParagraph = true;

        foreach ($lines as $line) {
            $isBullet = $line['bullet'];
            $text = $line['text'];

            $paragraph = $firstParagraph
                ? $body->getActiveParagraph()
                : $body->createParagraph();
            $firstParagraph = false;

            $this->styleBodyParagraph($paragraph, $isBullet, $layout);

            $run = $paragraph->createTextRun($text);
            $run->getFont()
                ->setSize($isBullet ? $layout['bulletFont'] : $layout['introFont'])
                ->setColor(new Color($isBullet ? 'FFE2E8F0' : 'FFF8FAFC'));
        }
    }

    /**
     * Prevent PhpPresentation's default spAutoFit from growing the box past the slide.
     */
    private function lockTextBox(RichText $shape): void
    {
        $shape->setAutoFit(RichText::AUTOFIT_NORMAL);
        $shape->setVerticalOverflow(RichText::OVERFLOW_CLIP);
        $shape->setHorizontalOverflow(RichText::OVERFLOW_CLIP);
        $shape->setInsetTop(0);
        $shape->setInsetBottom(0);
        $shape->setInsetLeft(0);
        $shape->setInsetRight(0);
    }

    /**
     * @param  list<array{bullet: bool, text: string}>  $lines
     * @return array{bulletFont: int, introFont: int, lineSpacing: int, bulletBefore: int, bulletAfter: int, introBefore: int, introAfter: int}
     */
    private function pickBodyLayout(array $lines, int $availableHeightPx): array
    {
        $candidates = [
            [
                'bulletFont' => 18,
                'introFont' => 19,
                'lineSpacing' => 128,
                'bulletBefore' => 7,
                'bulletAfter' => 7,
                'introBefore' => 2,
                'introAfter' => 10,
            ],
            [
                'bulletFont' => 16,
                'introFont' => 17,
                'lineSpacing' => 122,
                'bulletBefore' => 5,
                'bulletAfter' => 5,
                'introBefore' => 2,
                'introAfter' => 8,
            ],
            [
                'bulletFont' => 15,
                'introFont' => 16,
                'lineSpacing' => 118,
                'bulletBefore' => 4,
                'bulletAfter' => 4,
                'introBefore' => 1,
                'introAfter' => 6,
            ],
            [
                'bulletFont' => 14,
                'introFont' => 15,
                'lineSpacing' => 114,
                'bulletBefore' => 3,
                'bulletAfter' => 3,
                'introBefore' => 1,
                'introAfter' => 5,
            ],
            [
                'bulletFont' => 13,
                'introFont' => 14,
                'lineSpacing' => 110,
                'bulletBefore' => 2,
                'bulletAfter' => 2,
                'introBefore' => 0,
                'introAfter' => 4,
            ],
        ];

        foreach ($candidates as $candidate) {
            if ($this->estimateBodyHeightPx($lines, $candidate) <= $availableHeightPx) {
                return $candidate;
            }
        }

        return $candidates[array_key_last($candidates)];
    }

    /**
     * @param  list<array{bullet: bool, text: string}>  $lines
     * @param  array{bulletFont: int, introFont: int, lineSpacing: int, bulletBefore: int, bulletAfter: int, introBefore: int, introAfter: int}  $layout
     */
    private function estimateBodyHeightPx(array $lines, array $layout): float
    {
        $height = 0.0;

        foreach ($lines as $line) {
            $font = $line['bullet'] ? $layout['bulletFont'] : $layout['introFont'];
            $wrapped = $this->estimateWrappedLines(
                $line['text'],
                $font,
                indentChars: $line['bullet'] ? 6 : 0,
            );
            $before = $line['bullet'] ? $layout['bulletBefore'] : $layout['introBefore'];
            $after = $line['bullet'] ? $layout['bulletAfter'] : $layout['introAfter'];

            // PhpPresentation spacing values are in points; convert with 96dpi.
            $height += ($before + $after) * (96 / 72);
            $height += $wrapped * $font * (96 / 72) * ($layout['lineSpacing'] / 100);
        }

        return $height;
    }

    private function estimateWrappedLines(string $text, int $fontPt, int $indentChars): int
    {
        // Approximate average glyph width for Calibri/Arial-like fonts.
        $charsPerLine = (int) floor(self::CONTENT_WIDTH / max(1, $fontPt * 0.56)) - $indentChars;
        $charsPerLine = max(18, $charsPerLine);

        return max(1, (int) ceil(mb_strlen($text) / $charsPerLine));
    }

    /**
     * @param  array{bulletFont: int, introFont: int, lineSpacing: int, bulletBefore: int, bulletAfter: int, introBefore: int, introAfter: int}  $layout
     */
    private function styleBodyParagraph(Paragraph $paragraph, bool $isBullet, array $layout): void
    {
        $paragraph->setLineSpacingMode(Paragraph::LINE_SPACING_MODE_PERCENT);
        $paragraph->setLineSpacing($layout['lineSpacing']);
        $paragraph->setSpacingBefore($isBullet ? $layout['bulletBefore'] : $layout['introBefore']);
        $paragraph->setSpacingAfter($isBullet ? $layout['bulletAfter'] : $layout['introAfter']);

        $alignment = $paragraph->getAlignment();
        $alignment->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $alignment->setVertical(Alignment::VERTICAL_TOP);

        if ($isBullet) {
            $alignment->setMarginLeft(28);
            $alignment->setIndent(-18);

            $bullet = new Bullet;
            $bullet->setBulletType(Bullet::TYPE_BULLET)
                ->setBulletChar('•')
                ->setBulletColor(new Color('FF5EEAD4'));
            $paragraph->setBulletStyle($bullet);
        } else {
            $alignment->setMarginLeft(0);
            $alignment->setIndent(0);
            $paragraph->setBulletStyle((new Bullet)->setBulletType(Bullet::TYPE_NONE));
        }
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

            // Match present-mode markers, middle-dot paste, and numbered lists.
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

        // If a slide is mostly sentences without markers, treat later lines as bullets for clarity.
        if (count($lines) > 1) {
            $bulletCount = count(array_filter($lines, fn (array $l): bool => $l['bullet']));
            if ($bulletCount === 0) {
                $first = true;
                foreach ($lines as $i => $line) {
                    if ($first) {
                        $first = false;

                        continue;
                    }
                    $lines[$i]['bullet'] = true;
                }
            }
        }

        return $lines;
    }

    private function applyCoverBackground($slide, ?int $carParkId, ?string $cover = null): void
    {
        $hero = $cover === 'jha'
            ? $this->resolveCover->jhaAbsolutePath()
            : $this->resolveCover->absolutePath($carParkId);

        if (is_file($hero)) {
            $background = new BackgroundImage;
            $background->setPath($hero);
            $slide->setBackground($background);

            return;
        }

        $this->applyDarkBackground($slide);
    }

    private function applyDarkBackground($slide): void
    {
        $background = new BackgroundColor;
        $background->setColor(new Color('FF0F172A'));
        $slide->setBackground($background);
    }
}
