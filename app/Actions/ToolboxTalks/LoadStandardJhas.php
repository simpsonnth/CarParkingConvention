<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use App\Models\ToolboxTalk;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoadStandardJhas
{
    /**
     * Seed all configured JHA decks for a date from config/jha-templates.php.
     *
     * @return int Total slides written across all decks
     */
    public function handle(Carbon|string $date, bool $overwrite = true): int
    {
        $talkDate = Carbon::parse($date)->toDateString();
        /** @var array<string, array{label?: string, slides?: list<array{title: string, body?: string|null}>}> $templates */
        $templates = config('jha-templates', []);
        /** @var list<array{key: string}> $decks */
        $decks = config('toolbox-talks.jha_decks', []);

        return (int) DB::transaction(function () use ($talkDate, $templates, $decks, $overwrite): int {
            $total = 0;

            foreach ($decks as $deck) {
                $key = (string) ($deck['key'] ?? '');
                if ($key === '' || ! isset($templates[$key])) {
                    continue;
                }

                $talk = ToolboxTalk::firstOrCreateJha($talkDate, $key);
                $talk->load('slides');

                if ($talk->slides->isNotEmpty() && ! $overwrite) {
                    continue;
                }

                $talk->slides()->delete();

                $slides = $templates[$key]['slides'] ?? [];
                foreach (array_values($slides) as $order => $slide) {
                    $title = trim((string) ($slide['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }

                    $body = trim((string) ($slide['body'] ?? ''));
                    $talk->slides()->create([
                        'sort_order' => $order,
                        'title' => mb_substr($title, 0, 255),
                        'body' => $body !== '' ? $body : null,
                    ]);
                    $total++;
                }
            }

            return $total;
        });
    }
}
