<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use App\Models\CarPark;
use Illuminate\Support\Carbon;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\RichText\Paragraph;
use PhpOffice\PhpPresentation\Slide\Background\Color as BackgroundColor;
use PhpOffice\PhpPresentation\Slide\Background\Image as BackgroundImage;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Style\Color;

class ExportToolboxTalkPowerpoint
{
    /** Layout coordinates assume 16:9 at 96dpi (~960×540). */
    private const MARGIN_X = 56;

    private const CONTENT_WIDTH = 848;

    private const SLIDE_HEIGHT = 540;

    public function __construct(
        private readonly BuildToolboxTalkDeck $buildDeck,
    ) {}

    /**
     * @return array{filename: string, content: string}
     */
    public function handle(Carbon|string $date, ?int $carParkId = null): array
    {
        $talkDate = Carbon::parse($date)->toDateString();
        $deck = $this->buildDeck->handle($talkDate, $carParkId);

        $presentation = new PhpPresentation();
        $presentation->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);
        $presentation->removeSlideByIndex(0);

        $this->addTitleSlide($presentation, $talkDate, $carParkId);

        foreach ($deck as $slideData) {
            $this->addContentSlide($presentation, $slideData);
        }

        if ($deck === []) {
            $this->addContentSlide($presentation, [
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

        $suffix = $carParkId !== null ? '-park-'.$carParkId : '-core';

        return [
            'filename' => 'toolbox-talk-'.$talkDate.$suffix.'.pptx',
            'content' => $content,
        ];
    }

    private function addTitleSlide(PhpPresentation $presentation, string $talkDate, ?int $carParkId): void
    {
        $slide = $presentation->createSlide();
        $hero = public_path('images/guest-handout-hero.png');
        if (is_file($hero)) {
            $background = new BackgroundImage;
            $background->setPath($hero);
            $slide->setBackground($background);
        } else {
            $this->applyDarkBackground($slide);
        }

        $kicker = $slide->createRichTextShape()
            ->setHeight(36)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY(150);
        $kicker->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $kickerRun = $kicker->createTextRun(mb_strtoupper(__('toolbox_talks.section_core')));
        $kickerRun->getFont()->setBold(true)->setSize(14)->setColor(new Color('FF5EEAD4'));

        $title = $slide->createRichTextShape()
            ->setHeight(120)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY(200);
        $title->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $run = $title->createTextRun(__('toolbox_talks.pptx_title'));
        $run->getFont()->setBold(true)->setSize(36)->setColor(new Color('FFFFFFFF'));

        $sub = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY(340);
        $sub->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $subtitle = __('toolbox_talks.pptx_subtitle', ['date' => $talkDate]);
        if ($carParkId !== null) {
            $parkName = CarPark::query()->whereKey($carParkId)->value('name');
            if (is_string($parkName) && $parkName !== '') {
                $subtitle .= '  ·  '.$parkName;
            }
        }
        $subRun = $sub->createTextRun($subtitle);
        $subRun->getFont()->setSize(20)->setColor(new Color('FFCCFBF1'));
    }

    /**
     * @param  array{title: string, body: string, section_label?: string}  $slideData
     */
    private function addContentSlide(PhpPresentation $presentation, array $slideData): void
    {
        $slide = $presentation->createSlide();
        $this->applyDarkBackground($slide);

        $y = 36;
        $section = trim((string) ($slideData['section_label'] ?? ''));
        if ($section !== '') {
            $chip = $slide->createRichTextShape()
                ->setHeight(30)
                ->setWidth(self::CONTENT_WIDTH)
                ->setOffsetX(self::MARGIN_X)
                ->setOffsetY($y);
            $chipRun = $chip->createTextRun(mb_strtoupper($section));
            $chipRun->getFont()->setBold(true)->setSize(12)->setColor(new Color('FF5EEAD4'));
            $y += 34;
        }

        $titleShape = $slide->createRichTextShape()
            ->setHeight(78)
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY($y);
        $titlePara = $titleShape->getActiveParagraph();
        $titlePara->setLineSpacing(112);
        $titlePara->setSpacingAfter(8);
        $titleRun = $titleShape->createTextRun($slideData['title']);
        $titleRun->getFont()->setBold(true)->setSize(26)->setColor(new Color('FFFFFFFF'));
        $y += 86;

        $bodyText = trim((string) ($slideData['body'] ?? ''));
        if ($bodyText === '') {
            return;
        }

        $body = $slide->createRichTextShape()
            ->setHeight(max(100, self::SLIDE_HEIGHT - $y - 36))
            ->setWidth(self::CONTENT_WIDTH)
            ->setOffsetX(self::MARGIN_X)
            ->setOffsetY($y);

        $lines = $this->normalizeBodyLines($bodyText);
        $firstParagraph = true;

        foreach ($lines as $line) {
            $isBullet = $line['bullet'];
            $text = $line['text'];

            $paragraph = $firstParagraph
                ? $body->getActiveParagraph()
                : $body->createParagraph();
            $firstParagraph = false;

            $this->styleBodyParagraph($paragraph, $isBullet);

            $fontSize = $this->bodyFontSize(count($lines), $isBullet);
            $run = $paragraph->createTextRun($text);
            $run->getFont()
                ->setSize($fontSize)
                ->setColor(new Color($isBullet ? 'FFE2E8F0' : 'FFF8FAFC'));
        }
    }

    private function bodyFontSize(int $lineCount, bool $isBullet): int
    {
        if ($lineCount >= 8) {
            return $isBullet ? 15 : 16;
        }

        if ($lineCount >= 5) {
            return $isBullet ? 17 : 18;
        }

        return $isBullet ? 19 : 20;
    }

    private function styleBodyParagraph(Paragraph $paragraph, bool $isBullet): void
    {
        $paragraph->setLineSpacingMode(Paragraph::LINE_SPACING_MODE_PERCENT);
        $paragraph->setLineSpacing(140);
        $paragraph->setSpacingBefore($isBullet ? 10 : 6);
        $paragraph->setSpacingAfter($isBullet ? 12 : 14);

        $alignment = $paragraph->getAlignment();
        $alignment->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $alignment->setVertical(Alignment::VERTICAL_TOP);

        if ($isBullet) {
            // Hanging indent so wrapped lines align under the text, not the bullet.
            $alignment->setMarginLeft(36);
            $alignment->setIndent(-22);

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

    private function applyDarkBackground($slide): void
    {
        $background = new BackgroundColor;
        $background->setColor(new Color('FF0F172A'));
        $slide->setBackground($background);
    }
}
