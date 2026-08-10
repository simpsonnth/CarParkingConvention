<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ResendWebhookProcessor;
use App\Support\ResendWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class ResendWebhookController extends Controller
{
    public function __invoke(Request $request, ResendWebhookProcessor $processor): Response
    {
        $raw = $request->getContent();
        $secret = (string) config('services.resend.webhook_secret', '');

        if ($secret !== '') {
            try {
                ResendWebhookVerifier::verify($raw, [
                    'svix-id' => $request->header('svix-id'),
                    'svix-timestamp' => $request->header('svix-timestamp'),
                    'svix-signature' => $request->header('svix-signature'),
                ], $secret);
            } catch (UnexpectedValueException $e) {
                Log::warning('Resend webhook signature rejected', ['message' => $e->getMessage()]);

                return response('Invalid signature', 400);
            }
        } elseif (! app()->environment('local', 'testing')) {
            Log::error('RESEND_WEBHOOK_SECRET is not configured; rejecting webhook');

            return response('Webhook secret not configured', 503);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return response('Invalid JSON', 400);
        }

        $processor->handle($payload, $request->header('svix-id'));

        return response('ok', 200);
    }
}
