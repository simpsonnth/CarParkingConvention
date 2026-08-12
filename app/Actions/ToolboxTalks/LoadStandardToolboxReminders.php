<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use App\Models\ToolboxTalk;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoadStandardToolboxReminders
{
    /**
     * Replace Core slides for the date with the static reminder sections from lang.
     *
     * @return int Number of slides created
     */
    public function handle(Carbon|string $date, bool $overwrite = false): int
    {
        $talk = ToolboxTalk::firstOrCreateCore($date);
        $talk->load('slides');

        if ($talk->slides->isNotEmpty() && ! $overwrite) {
            return 0;
        }

        $sections = __('toolbox_talk_reminders.sections');
        if (! is_array($sections) || $sections === []) {
            return 0;
        }

        return DB::transaction(function () use ($talk, $sections): int {
            $talk->slides()->delete();

            $order = 0;
            foreach ($sections as $section) {
                if (! is_array($section)) {
                    continue;
                }

                $title = trim((string) ($section['title'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $parts = [];
                $intro = trim((string) ($section['intro'] ?? ''));
                if ($intro !== '') {
                    $parts[] = $intro;
                }

                $items = $section['items'] ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $text = trim((string) $item);
                        if ($text !== '') {
                            $parts[] = '• '.$text;
                        }
                    }
                }

                $talk->slides()->create([
                    'sort_order' => $order++,
                    'title' => $title,
                    'body' => implode("\n\n", $parts),
                ]);
            }

            return $order;
        });
    }
}
