<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const TYPE_FIELD_UPDATE = 'field_update';

    public const TYPE_CAR_PARK_CHANGE = 'car_park_change';

    public const TYPE_CANCELLATION = 'cancellation';

    public const TYPE_ADDITION = 'addition';

    public const TYPE_EMAIL_REQUEST = 'email_request';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_FIELD_UPDATE,
        self::TYPE_CAR_PARK_CHANGE,
        self::TYPE_CANCELLATION,
        self::TYPE_ADDITION,
        self::TYPE_EMAIL_REQUEST,
    ];

    /** @var list<string> */
    public const AUTO_APPLY_FIELDS = [
        'name',
        'vehicle_registration',
        'email',
        'contact_number',
        'vehicle_type',
    ];

    protected $fillable = [
        'request_type',
        'parking_registration_id',
        'name',
        'congregation',
        'notification_email',
        'notes',
        'payload',
        'before_snapshot',
        'admin_notes',
        'status',
        'actioned_at',
        'actioned_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'before_snapshot' => 'array',
        'actioned_at' => 'datetime',
    ];

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function parkingRegistration(): BelongsTo
    {
        return $this->belongsTo(ParkingRegistration::class)->withTrashed();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function requiresApproval(): bool
    {
        return in_array($this->request_type, [
            self::TYPE_CAR_PARK_CHANGE,
            self::TYPE_CANCELLATION,
            self::TYPE_ADDITION,
        ], true);
    }

    public function isStructured(): bool
    {
        return filled($this->request_type) && in_array($this->request_type, self::TYPES, true);
    }

    public function wasAutoCompleted(): bool
    {
        return $this->isCompleted()
            && $this->request_type === self::TYPE_FIELD_UPDATE
            && $this->actioned_by === null;
    }

    /**
     * Other requests from the same person (name + congregation), case-insensitive.
     *
     * @param  list<int>|null  $excludeIds
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForSamePerson($query, string $name, string $congregation, ?array $excludeIds = null)
    {
        $query
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])
            ->whereRaw('LOWER(TRIM(congregation)) = ?', [mb_strtolower(trim($congregation))]);

        if ($excludeIds !== null && $excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query;
    }

    /**
     * Requests from the same congregation, case-insensitive.
     *
     * @param  list<int>|null  $excludeIds
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForSameCongregation($query, string $congregation, ?array $excludeIds = null)
    {
        $query->whereRaw('LOWER(TRIM(congregation)) = ?', [mb_strtolower(trim($congregation))]);

        if ($excludeIds !== null && $excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query;
    }
}
