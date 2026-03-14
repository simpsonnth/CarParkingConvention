<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    protected array $supported = ['en', 'pt', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale'));
        if (in_array($locale, $this->supported, true)) {
            app()->setLocale($locale);
        }
        return $next($request);
    }
}
