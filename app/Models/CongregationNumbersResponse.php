<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CongregationNumbersResponse extends Model
{
    use SoftDeletes;

    public const COACH_SIZE_MINIBUS = 'minibus';

    public const COACH_SIZE_SMALL = 'small_coach';

    public const COACH_SIZE_LARGE = 'large_coach';

    /** @return list<string> */
    public static function coachSizeKeys(): array
    {
        return [
            self::COACH_SIZE_MINIBUS,
            self::COACH_SIZE_SMALL,
            self::COACH_SIZE_LARGE,
        ];
    }

    protected $fillable = [
        'congregation_id',
        'car_park_tickets_count',
        'organizes_coach',
        'sharing_coach_with_others',
        'shared_with_congregation_ids',
        'coach_size',
        'disabled_parking_required',
        'disabled_parking_count',
        'submitted_locale',
    ];

    protected $casts = [
        'organizes_coach' => 'boolean',
        'sharing_coach_with_others' => 'boolean',
        'disabled_parking_required' => 'boolean',
        'shared_with_congregation_ids' => 'array',
    ];

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    /** @return list<int> */
    public function normalizedSharedCongregationIds(): array
    {
        $raw = $this->shared_with_congregation_ids ?? [];
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw))));
    }
}
