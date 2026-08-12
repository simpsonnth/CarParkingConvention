<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use Illuminate\Support\Carbon;
use App\Models\CarPark;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Slide\Background\Color as BackgroundColor;
use PhpOffice\PhpPresentation\Slide\Background\Image as BackgroundImage;

class ExportToolboxTalkPowerpoint
{
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
            $background = new BackgroundColor;
            $background->setColor(new Color('FF0F172A'));
            $slide->setBackground($background);
        }

        $title = $slide->createRichTextShape()
            ->setHeight(200)
            ->setWidth(860)
            ->setOffsetX(50)
            ->setOffsetY(180);
        $title->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $run = $title->createTextRun(__('toolbox_talks.pptx_title'));
        $run->getFont()->setBold(true)->setSize(40)->setColor(new Color('FFFFFFFF'));

        $sub = $slide->createRichTextShape()
            ->setHeight(120)
            ->setWidth(860)
            ->setOffsetX(50)
            ->setOffsetY(360);
        $sub->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $subtitle = __('toolbox_talks.pptx_subtitle', ['date' => $talkDate]);
        if ($carParkId !== null) {
            $parkName = CarPark::query()->whereKey($carParkId)->value('name');
            if (is_string($parkName) && $parkName !== '') {
                $subtitle .= ' · '.$parkName;
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
        $background = new BackgroundColor;
        $background->setColor(new Color('FF0F172A'));
        $slide->setBackground($background);

        $section = trim((string) ($slideData['section_label'] ?? ''));
        if ($section !== '') {
            $chip = $slide->createRichTextShape()
                ->setHeight(40)
                ->setWidth(860)
                ->setOffsetX(50)
                ->setOffsetY(40);
            $chipRun = $chip->createTextRun(mb_strtoupper($section));
            $chipRun->getFont()->setBold(true)->setSize(12)->setColor(new Color('FF5EEAD4'));
        }

        $title = $slide->createRichTextShape()
            ->setHeight(140)
            ->setWidth(860)
            ->setOffsetX(50)
            ->setOffsetY(90);
        $titleRun = $title->createTextRun($slideData['title']);
        $titleRun->getFont()->setBold(true)->setSize(32)->setColor(new Color('FFFFFFFF'));

        $bodyText = trim((string) ($slideData['body'] ?? ''));
        if ($bodyText === '') {
            return;
        }

        $body = $slide->createRichTextShape()
            ->setHeight(360)
            ->setWidth(860)
            ->setOffsetX(50)
            ->setOffsetY(240);

        $lines = preg_split("/\R/u", $bodyText) ?: [$bodyText];
        $first = true;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (! $first) {
                $body->createBreak();
            }
            $first = false;
            $run = $body->createTextRun($line);
            $run->getFont()->setSize(18)->setColor(new Color('FFE2E8F0'));
        }
    }
}
