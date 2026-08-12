<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use App\Models\CarPark;
use App\Models\ToolboxTalk;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BuildToolboxTalkDeck
{
    /**
     * Build Core → park cover → park add-on → filtered JHA (present mode).
     *
     * @return list<array{type: string, title: string, body: string, section: string, section_label: string, car_park_id?: int|null, cover?: string|null}>
     */
    public function handle(Carbon|string $date, ?int $carParkId = null): array
    {
        $talkDate = Carbon::parse($date)->toDateString();
        $slides = $this->coreSlides($talkDate);

        $parkName = null;
        if ($carParkId !== null) {
            $park = CarPark::query()->find($carParkId);
            if ($park !== null) {
                $parkName = (string) $park->name;
                array_push($slides, ...$this->parkSectionSlides($talkDate, $park));
            }
        }

        array_push($slides, ...$this->jhaSectionSlides($talkDate, $parkName));

        return $slides;
    }

    /**
     * Build Core → every car park → all JHA decks for downloads.
     *
     * @return list<array{type: string, title: string, body: string, section: string, section_label: string, car_park_id?: int|null, cover?: string|null}>
     */
    public function handleFull(Carbon|string $date): array
    {
        $talkDate = Carbon::parse($date)->toDateString();
        $slides = $this->coreSlides($talkDate);

        $parks = CarPark::query()->orderBy('name')->get(['id', 'name']);
        foreach ($parks as $park) {
            array_push($slides, ...$this->parkSectionSlides($talkDate, $park));
        }

        array_push($slides, ...$this->jhaSectionSlides($talkDate, parkName: null, includeAll: true));

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

    /**
     * @return list<array{type: string, title: string, body: string, section: string, section_label: string, cover?: string}>
     */
    private function jhaSectionSlides(string $talkDate, ?string $parkName, bool $includeAll = false): array
    {
        /** @var list<array{key: string, label: string, park_match: list<string>|null}> $decks */
        $decks = config('toolbox-talks.jha_decks', []);
        if ($decks === []) {
            return [];
        }

        $selected = [];
        foreach ($decks as $deck) {
            $key = (string) ($deck['key'] ?? '');
            if ($key === '') {
                continue;
            }

            if ($includeAll || $parkName === null) {
                $selected[] = $deck;

                continue;
            }

            $match = $deck['park_match'] ?? null;
            if ($match === null) {
                $selected[] = $deck;

                continue;
            }

            if (! is_array($match)) {
                continue;
            }

            $haystack = Str::lower($parkName);
            foreach ($match as $needle) {
                if (is_string($needle) && $needle !== '' && str_contains($haystack, Str::lower($needle))) {
                    $selected[] = $deck;
                    break;
                }
            }
        }

        if ($selected === []) {
            return [];
        }

        $slides = [
            [
                'type' => 'cover',
                'title' => __('toolbox_talks.jha_cover_title'),
                'body' => __('toolbox_talks.jha_cover_subtitle'),
                'section' => ToolboxTalk::SCOPE_JHA,
                'section_label' => __('toolbox_talks.section_jha'),
                'cover' => 'jha',
            ],
        ];

        foreach ($selected as $deck) {
            $key = (string) $deck['key'];
            $label = (string) ($deck['label'] ?? $key);
            $talk = ToolboxTalk::findJhaForDate($talkDate, $key);
            if ($talk === null) {
                continue;
            }

            $sectionLabel = __('toolbox_talks.section_jha_doc', ['name' => $label]);

            foreach ($talk->slides as $slide) {
                $slides[] = [
                    'type' => 'content',
                    'title' => $slide->title,
                    'body' => (string) ($slide->body ?? ''),
                    'section' => ToolboxTalk::SCOPE_JHA,
                    'section_label' => $sectionLabel,
                ];
            }
        }

        // Only keep the cover if at least one JHA content slide exists.
        if (count($slides) === 1) {
            return [];
        }

        return $slides;
    }
}
