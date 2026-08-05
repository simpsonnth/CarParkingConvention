<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'name',
        'congregation',
        'notes',
        'admin_notes',
        'status',
        'actioned_at',
        'actioned_by',
    ];

    protected $casts = [
        'actioned_at' => 'datetime',
    ];

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
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
