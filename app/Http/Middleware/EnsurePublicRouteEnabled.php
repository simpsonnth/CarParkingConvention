<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\PublicRouteAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicRouteEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if ($routeName === null || PublicRouteAccess::isEnabled($routeName)) {
            return $next($request);
        }

        $definition = PublicRouteAccess::definitionFor($routeName);

        return response()->view('public.route-closed', [
            'pageTitle' => $definition ? __($definition['label_key']) : __('routes_list.closed_heading'),
            'message' => __(PublicRouteAccess::closedMessageKey($routeName)),
        ], 200);
    }
}
