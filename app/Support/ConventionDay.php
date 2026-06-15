<?php

namespace App\Support;

class ConventionDay
{
    public const FRIDAY = 'Friday';

    public const SATURDAY = 'Saturday';

    public const SUNDAY = 'Sunday';

    public const ALL_DAYS = 'all_days';

    /** @return list<string> */
    public static function singleDayKeys(): array
    {
        return [self::FRIDAY, self::SATURDAY, self::SUNDAY];
    }

    /** @return list<string> */
    public static function lessonDayKeys(): array
    {
        return array_merge(self::singleDayKeys(), [self::ALL_DAYS]);
    }

    public static function label(string $day): string
    {
        return match ($day) {
            self::FRIDAY => __('management.convention_day.friday'),
            self::SATURDAY => __('management.convention_day.saturday'),
            self::SUNDAY => __('management.convention_day.sunday'),
            self::ALL_DAYS => __('management.convention_day.all_days'),
            default => $day,
        };
    }

    public static function publicLabel(string $day): string
    {
        return match ($day) {
            self::FRIDAY => __('lessons_learned.convention_day_friday'),
            self::SATURDAY => __('lessons_learned.convention_day_saturday'),
            self::SUNDAY => __('lessons_learned.convention_day_sunday'),
            self::ALL_DAYS => __('lessons_learned.convention_day_all_days'),
            default => $day,
        };
    }
}
