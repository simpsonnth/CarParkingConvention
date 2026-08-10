<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutboundEmail extends Model
{
    public const TYPE_CAR_PARK_TICKETS = 'car_park_tickets';

    public const TYPE_CANCELLATION = 'ticket_cancellation';

    public const TYPE_CHANGE_DECLINE = 'ticket_change_decline';

    public const TYPE_HOTEL_DECLINE = 'hotel_guest_decline';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const PROVIDER_DELIVERED = 'delivered';

    public const PROVIDER_BOUNCED = 'bounced';

    public const PROVIDER_COMPLAINED = 'complained';

    public const PROVIDER_DELAYED = 'delayed';

    public const PROVIDER_SENT = 'sent';

    public const PROVIDER_OPENED = 'opened';

    protected $fillable = [
        'type',
        'status',
        'provider_status',
        'provider_email_id',
        'provider_detail',
        'to_email',
        'payload',
        'available_at',
        'attempts',
        'last_error',
        'sent_at',
        'delivered_at',
        'opened_at',
        'bounced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'bounced_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(OutboundEmailEvent::class);
    }

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

    public function isBounced(): bool
    {
        return $this->provider_status === self::PROVIDER_BOUNCED || $this->bounced_at !== null;
    }

    public function isDelivered(): bool
    {
        return $this->provider_status === self::PROVIDER_DELIVERED || $this->delivered_at !== null;
    }
}
