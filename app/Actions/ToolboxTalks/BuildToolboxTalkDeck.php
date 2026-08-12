<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use App\Models\CarPark;
use App\Models\ToolboxTalk;
use Illuminate\Support\Carbon;

class BuildToolboxTalkDeck
{
    /**
     * Build Core → park cover → park add-on slides for a single park (present mode).
     *
     * @return list<array{type: string, title: string, body: string, section: string, section_label: string, car_park_id?: int|null}>
     */
    public function handle(Carbon|string $date, ?int $carParkId = null): array
    {
        $talkDate = Carbon::parse($date)->toDateString();
        $slides = $this->coreSlides($talkDate);

        if ($carParkId !== null) {
            $park = CarPark::query()->find($carParkId);
            if ($park !== null) {
                array_push($slides, ...$this->parkSectionSlides($talkDate, $park));
            }
        }

        return $slides;
    }

    /**
     * Build Core → every car park (cover + add-ons) for downloads.
     *
     * @return list<array{type: string, title: string, body: string, section: string, section_label: string, car_park_id?: int|null}>
     */
    public function handleFull(Carbon|string $date): array
    {
        $talkDate = Carbon::parse($date)->toDateString();
        $slides = $this->coreSlides($talkDate);

        $parks = CarPark::query()->orderBy('name')->get(['id', 'name']);
        foreach ($parks as $park) {
            array_push($slides, ...$this->parkSectionSlides($talkDate, $park));
        }

        return $slides;
    }

    /**
     * @return list<array{type: string, title: string, body: string, section: string, section_label: string}>
     */
    private function coreSlides(string $talkDate): array
    {
        $slides = [];
        $core = ToolboxTalk::findCoreForDate($talkDate);
        if ($core === null) {
            return $slides;
        }

        foreach ($core->slides as $slide) {
            $slides[] = [
                'type' => 'content',
                'title' => $slide->title,
                'body' => (string) ($slide->body ?? ''),
                'section' => ToolboxTalk::SCOPE_CORE,
                'section_label' => __('toolbox_talks.section_core'),
            ];
        }

        return $slides;
    }

    /**
     * @return list<array{type: string, title: string, body: string, section: string, section_label: string, car_park_id: int}>
     */
    private function parkSectionSlides(string $talkDate, CarPark $park): array
    {
        $label = __('toolbox_talks.section_park', ['park' => $park->name]);
        $slides = [
            [
                'type' => 'cover',
                'title' => $park->name,
                'body' => __('toolbox_talks.park_cover_subtitle'),
                'section' => 'cover',
                'section_label' => $label,
                'car_park_id' => $park->id,
            ],
        ];

        $parkTalk = ToolboxTalk::findParkForDate($talkDate, $park->id);
        if ($parkTalk === null) {
            return $slides;
        }

        foreach ($parkTalk->slides as $slide) {
            $slides[] = [
                'type' => 'content',
                'title' => $slide->title,
                'body' => (string) ($slide->body ?? ''),
                'section' => ToolboxTalk::SCOPE_PARK,
                'section_label' => $label,
                'car_park_id' => $park->id,
            ];
        }

        return $slides;
    }
}
