<?php

declare(strict_types=1);

namespace App\Actions\ToolboxTalks;

use App\Models\CarPark;
use Illuminate\Support\Str;

class ResolveToolboxTalkCover
{
    /**
     * Absolute filesystem path for a cover image (always exists when default is present).
     */
    public function absolutePath(?int $carParkId = null): string
    {
        $relative = $this->relativePath($carParkId);
        $absolute = public_path($relative);

        if (is_file($absolute)) {
            return $absolute;
        }

        $fallback = public_path((string) config('toolbox-talks.cover_default', 'images/guest-handout-hero.png'));
        if (is_file($fallback)) {
            return $fallback;
        }

        $legacy = public_path('images/guest-handout-hero.png');

        return is_file($legacy) ? $legacy : $absolute;
    }

    /**
     * Public URL for present-mode / browser use.
     */
    public function url(?int $carParkId = null): string
    {
        $relative = $this->relativePath($carParkId);
        if (is_file(public_path($relative))) {
            return asset($relative);
        }

        $default = (string) config('toolbox-talks.cover_default', 'images/guest-handout-hero.png');
        if (is_file(public_path($default))) {
            return asset($default);
        }

        return asset('images/guest-handout-hero.png');
    }

    public function jhaAbsolutePath(): string
    {
        $relative = (string) config('toolbox-talks.cover_jha', 'images/toolbox-covers/jha.png');
        $absolute = public_path($relative);
        if (is_file($absolute)) {
            return $absolute;
        }

        return $this->absolutePath(null);
    }

    public function jhaUrl(): string
    {
        $relative = (string) config('toolbox-talks.cover_jha', 'images/toolbox-covers/jha.png');
        if (is_file(public_path($relative))) {
            return asset($relative);
        }

        return $this->url(null);
    }

    public function relativePath(?int $carParkId = null): string
    {
        $default = (string) config('toolbox-talks.cover_default', 'images/toolbox-covers/default.png');

        if ($carParkId === null) {
            return $default;
        }

        $parkName = CarPark::query()->whereKey($carParkId)->value('name');
        if (is_string($parkName) && $parkName !== '') {
            $byName = config('toolbox-talks.cover_by_name', []);
            if (is_array($byName)) {
                foreach ($byName as $name => $path) {
                    if (! is_string($name) || ! is_string($path)) {
                        continue;
                    }
                    if (Str::lower($name) === Str::lower($parkName)) {
                        return $path;
                    }
                }
            }
        }

        /** @var list<string> $pool */
        $pool = array_values(array_filter(
            config('toolbox-talks.cover_pool', [$default]),
            fn ($path): bool => is_string($path) && $path !== '',
        ));

        if ($pool === []) {
            return $default;
        }

        $index = abs($carParkId - 1) % count($pool);

        return $pool[$index];
    }
}
