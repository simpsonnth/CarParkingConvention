<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use App\Models\CarPark;
use App\Models\ToolboxTalk;
use Illuminate\Support\Carbon;

class BuildToolboxTalkDeck
{
    /**
     * Build Core → Park add-on slides for presentation.
     *
     * @return list<array{title: string, body: string, section: string, section_label: string}>
     */
    public function handle(Carbon|string $date, ?int $carParkId = null): array
    {
        $talkDate = Carbon::parse($date)->toDateString();
        $slides = [];

        $core = ToolboxTalk::findCoreForDate($talkDate);
        if ($core !== null) {
            foreach ($core->slides as $slide) {
                $slides[] = [
                    'title' => $slide->title,
                    'body' => (string) ($slide->body ?? ''),
                    'section' => ToolboxTalk::SCOPE_CORE,
                    'section_label' => __('toolbox_talks.section_core'),
                ];
            }
        }

        if ($carParkId !== null) {
            $park = CarPark::query()->find($carParkId);
            $parkTalk = ToolboxTalk::findParkForDate($talkDate, $carParkId);
            if ($parkTalk !== null && $park !== null) {
                $label = __('toolbox_talks.section_park', ['park' => $park->name]);
                foreach ($parkTalk->slides as $slide) {
                    $slides[] = [
                        'title' => $slide->title,
                        'body' => (string) ($slide->body ?? ''),
                        'section' => ToolboxTalk::SCOPE_PARK,
                        'section_label' => $label,
                    ];
                }
            }
        }

        return $slides;
    }
}
