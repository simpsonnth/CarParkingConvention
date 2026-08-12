<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolboxTalkSlide extends Model
{
    protected $fillable = [
        'toolbox_talk_id',
        'sort_order',
        'title',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function talk(): BelongsTo
    {
        return $this->belongsTo(ToolboxTalk::class, 'toolbox_talk_id');
    }
}
