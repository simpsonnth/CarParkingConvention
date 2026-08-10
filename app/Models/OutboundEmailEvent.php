<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundEmailEvent extends Model
{
    protected $fillable = [
        'outbound_email_id',
        'provider',
        'event_type',
        'provider_email_id',
        'svix_id',
        'to_email',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function outboundEmail(): BelongsTo
    {
        return $this->belongsTo(OutboundEmail::class);
    }
}
