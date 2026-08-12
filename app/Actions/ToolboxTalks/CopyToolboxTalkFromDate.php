<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use App\Models\ToolboxTalk;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CopyToolboxTalkFromDate
{
    /**
     * Clone slides from a source date onto the target talk deck (replacing existing slides).
     */
    public function handle(ToolboxTalk $target, Carbon|string $sourceDate): int
    {
        $sourceDateString = Carbon::parse($sourceDate)->toDateString();

        $source = ToolboxTalk::query()
            ->whereDate('talk_date', $sourceDateString)
            ->where('deck_key', $target->deck_key)
            ->with('slides')
            ->first();

        if ($source === null) {
            return 0;
        }

        return DB::transaction(function () use ($target, $source): int {
            $target->slides()->delete();

            $copied = 0;
            foreach ($source->slides as $slide) {
                $target->slides()->create([
                    'sort_order' => $slide->sort_order,
                    'title' => $slide->title,
                    'body' => $slide->body,
                ]);
                $copied++;
            }

            return $copied;
        });
    }
}
