<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OutboundEmail extends Model
{
    public const TYPE_CAR_PARK_TICKETS = 'car_park_tickets';

    public const TYPE_CANCELLATION = 'ticket_cancellation';

    public const TYPE_CHANGE_DECLINE = 'ticket_change_decline';

    public const TYPE_HOTEL_DECLINE = 'hotel_guest_decline';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'type',
        'status',
        'to_email',
        'payload',
        'available_at',
        'attempts',
        'last_error',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * @param  Builder<OutboundEmail>  $query
     * @return Builder<OutboundEmail>
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->where(function (Builder $q): void {
                $q->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            });
    }
}
