<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OutboundEmail;
use App\Models\OutboundEmailEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResendWebhookProcessor
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, ?string $svixId = null): OutboundEmailEvent
    {
        $type = (string) ($payload['type'] ?? '');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $providerEmailId = isset($data['email_id']) ? (string) $data['email_id'] : null;
        $toList = $this->recipientEmails($data);
        $primaryTo = $toList[0] ?? null;
        $occurredAt = $this->parseOccurredAt($payload, $data);

        if ($svixId !== null && $svixId !== '') {
            $existing = OutboundEmailEvent::query()->where('svix_id', $svixId)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($payload, $type, $data, $providerEmailId, $primaryTo, $toList, $occurredAt, $svixId): OutboundEmailEvent {
            $outbound = $this->matchOutboundEmail($providerEmailId, $toList);

            if ($outbound !== null) {
                $this->applyProviderStatus($outbound, $type, $data, $providerEmailId, $occurredAt);
            }

            return OutboundEmailEvent::query()->create([
                'outbound_email_id' => $outbound?->id,
                'provider' => 'resend',
                'event_type' => $type !== '' ? $type : 'unknown',
                'provider_email_id' => $providerEmailId,
                'svix_id' => $svixId !== '' ? $svixId : null,
                'to_email' => $primaryTo,
                'payload' => $payload,
                'occurred_at' => $occurredAt,
            ]);
        });
    }

    /**
     * @param  list<string>  $toList
     */
    private function matchOutboundEmail(?string $providerEmailId, array $toList): ?OutboundEmail
    {
        if ($providerEmailId !== null && $providerEmailId !== '') {
            $byId = OutboundEmail::query()
                ->where('provider_email_id', $providerEmailId)
                ->orderByDesc('id')
                ->first();
            if ($byId !== null) {
                return $byId;
            }
        }

        foreach ($toList as $to) {
            $match = OutboundEmail::query()
                ->where('to_email', $to)
                ->where('status', OutboundEmail::STATUS_SENT)
                ->where(function ($q) use ($providerEmailId): void {
                    $q->whereNull('provider_email_id');
                    if ($providerEmailId !== null && $providerEmailId !== '') {
                        $q->orWhere('provider_email_id', $providerEmailId);
                    }
                })
                ->where('sent_at', '>=', now()->subDays(14))
                ->orderByDesc('sent_at')
                ->orderByDesc('id')
                ->first();

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyProviderStatus(
        OutboundEmail $outbound,
        string $type,
        array $data,
        ?string $providerEmailId,
        ?Carbon $occurredAt,
    ): void {
        $updates = [];

        if ($providerEmailId !== null && $providerEmailId !== '' && $outbound->provider_email_id === null) {
            $updates['provider_email_id'] = $providerEmailId;
        }

        if ($type === 'email.delivered') {
            $updates['provider_status'] = OutboundEmail::PROVIDER_DELIVERED;
            $updates['delivered_at'] = $occurredAt ?? now();
            $updates['provider_detail'] = null;
        } elseif ($type === 'email.bounced') {
            $bounce = is_array($data['bounce'] ?? null) ? $data['bounce'] : [];
            $detail = trim(implode(' — ', array_filter([
                isset($bounce['type']) ? (string) $bounce['type'] : null,
                isset($bounce['subType']) ? (string) $bounce['subType'] : null,
                isset($bounce['message']) ? (string) $bounce['message'] : null,
            ])));

            $updates['provider_status'] = OutboundEmail::PROVIDER_BOUNCED;
            $updates['bounced_at'] = $occurredAt ?? now();
            $updates['provider_detail'] = $detail !== '' ? $detail : 'Bounced';
        } elseif ($type === 'email.complained') {
            $updates['provider_status'] = OutboundEmail::PROVIDER_COMPLAINED;
            $updates['provider_detail'] = 'Marked as spam by recipient';
        } elseif ($type === 'email.delivery_delayed') {
            // Don't overwrite a stronger status.
            if (! in_array($outbound->provider_status, [
                OutboundEmail::PROVIDER_DELIVERED,
                OutboundEmail::PROVIDER_BOUNCED,
                OutboundEmail::PROVIDER_COMPLAINED,
            ], true)) {
                $updates['provider_status'] = OutboundEmail::PROVIDER_DELAYED;
                $updates['provider_detail'] = 'Delivery delayed';
            }
        } elseif ($type === 'email.sent') {
            if ($outbound->provider_status === null) {
                $updates['provider_status'] = OutboundEmail::PROVIDER_SENT;
            }
        }

        if ($updates !== []) {
            $outbound->update($updates);
            Log::info('Outbound email provider status updated from Resend webhook', [
                'outbound_email_id' => $outbound->id,
                'event_type' => $type,
                'provider_email_id' => $providerEmailId,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function recipientEmails(array $data): array
    {
        $raw = $data['to'] ?? [];
        if (is_string($raw)) {
            $raw = [$raw];
        }
        if (! is_array($raw)) {
            return [];
        }

        $emails = [];
        foreach ($raw as $item) {
            $email = strtolower(trim((string) $item));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = $email;
            }
        }

        return array_values($emails);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $data
     */
    private function parseOccurredAt(array $payload, array $data): ?Carbon
    {
        foreach ([$payload['created_at'] ?? null, $data['created_at'] ?? null] as $value) {
            if (is_string($value) && $value !== '') {
                try {
                    return Carbon::parse($value);
                } catch (\Throwable) {
                    // continue
                }
            }
        }

        return now();
    }
}
