<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        $redirect = $user && $user->can('dashboard.view')
            ? route('dashboard', absolute: false).'?verified=1'
            : route('attendant.scan', absolute: false).'?verified=1';

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended($redirect);
    }
}
