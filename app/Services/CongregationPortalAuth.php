<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

final class CongregationPortalAuth
{
    private const SESSION_KEY = 'congregation_portal_id';

    public function passwordIsConfigured(): bool
    {
        $hash = Setting::get('congregation_portal_password');

        return is_string($hash) && $hash !== '';
    }

    public function attempt(string $uuid, string $password): ?Congregation
    {
        $uuid = trim($uuid);
        $throttleKey = 'congregation-portal:'.request()->ip().':'.$uuid;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return null;
        }

        RateLimiter::hit($throttleKey, 60);

        $congregation = Congregation::query()->where('uuid', $uuid)->first();
        $hash = Setting::get('congregation_portal_password');

        if ($congregation === null || ! is_string($hash) || $hash === '' || ! Hash::check($password, $hash)) {
            return null;
        }

        RateLimiter::clear($throttleKey);

        return $congregation;
    }

    public function login(Congregation $congregation): void
    {
        session([self::SESSION_KEY => $congregation->id]);
    }

    public function logout(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function authenticatedCongregation(): ?Congregation
    {
        $id = session(self::SESSION_KEY);
        if (! is_int($id) && ! (is_string($id) && ctype_digit($id))) {
            return null;
        }

        return Congregation::query()->with('numbersResponse')->find((int) $id);
    }

    public static function setPassword(string $plainPassword): void
    {
        Setting::set('congregation_portal_password', Hash::make($plainPassword));
    }
}
