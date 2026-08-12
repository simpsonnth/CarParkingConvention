<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use App\Models\CarPark;
use App\Models\ToolboxTalk;
use Illuminate\Support\Carbon;

class BuildToolboxTalkDeck
{
    /**
     * Build Core → park cover → park add-on slides for presentation.
     *
     * @return list<array{type: string, title: string, body: string, section: string, section_label: string, car_park_id?: int|null}>
     */
    public function handle(Carbon|string $date, ?int $carParkId = null): array
    {
        $talkDate = Carbon::parse($date)->toDateString();
        $slides = [];

        $core = ToolboxTalk::findCoreForDate($talkDate);
        if ($core !== null) {
            foreach ($core->slides as $slide) {
                $slides[] = [
                    'type' => 'content',
                    'title' => $slide->title,
                    'body' => (string) ($slide->body ?? ''),
                    'section' => ToolboxTalk::SCOPE_CORE,
                    'section_label' => __('toolbox_talks.section_core'),
                ];
            }
        }

        if ($carParkId !== null) {
            $park = CarPark::query()->find($carParkId);
            if ($park !== null) {
                $label = __('toolbox_talks.section_park', ['park' => $park->name]);

                $slides[] = [
                    'type' => 'cover',
                    'title' => $park->name,
                    'body' => __('toolbox_talks.park_cover_subtitle'),
                    'section' => 'cover',
                    'section_label' => $label,
                    'car_park_id' => $park->id,
                ];

                $parkTalk = ToolboxTalk::findParkForDate($talkDate, $carParkId);
                if ($parkTalk !== null) {
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
                }
            }
        }

        return $slides;
    }
}
