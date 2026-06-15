<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonLearned extends Model
{
    protected $table = 'lessons_learned';

    public const SOURCE_PUBLIC = 'public';

    public const SOURCE_ADMIN = 'admin';

    public const CATEGORY_PARKING = 'parking';

    public const CATEGORY_REGISTRATION = 'registration';

    public const CATEGORY_OPERATIONS = 'operations';

    public const CATEGORY_OTHER = 'other';

    /** @return list<string> */
    public static function sourceKeys(): array
    {
        return [self::SOURCE_PUBLIC, self::SOURCE_ADMIN];
    }

    /** @return list<string> */
    public static function categoryKeys(): array
    {
        return [
            self::CATEGORY_PARKING,
            self::CATEGORY_REGISTRATION,
            self::CATEGORY_OPERATIONS,
            self::CATEGORY_OTHER,
        ];
    }

    protected $fillable = [
        'source',
        'created_by_user_id',
        'reporter_name',
        'category',
        'convention_day',
        'title',
        'worked_well',
        'didnt_work_well',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
