<?php

declare(strict_types=1);

use App\Livewire\Admin\OutboundEmails;
use App\Models\OutboundEmail;
use App\Models\OutboundEmailEvent;
use App\Models\User;
use App\Services\ResendWebhookProcessor;
use App\Support\MailSendingQuota;
use App\Support\ResendWebhookVerifier;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

test('admin can view outbound email log', function () {
    $admin = User::factory()->admin()->create();

    OutboundEmail::query()->create([
        'type' => OutboundEmail::TYPE_CAR_PARK_TICKETS,
        'status' => OutboundEmail::STATUS_SENT,
        'to_email' => 'guest@example.test',
        'payload' => ['registration_ids' => [1], 'note' => null],
        'attempts' => 1,
        'sent_at' => now(),
        'provider_status' => OutboundEmail::PROVIDER_DELIVERED,
        'delivered_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(OutboundEmails::class)
        ->assertOk()
        ->assertSee('guest@example.test')
        ->assertSee(__('management.outbound_emails.provider_delivered'));
});

test('resend webhook marks matched outbound email as opened', function () {
    Config::set('services.resend.webhook_secret', '');

    $outbound = OutboundEmail::query()->create([
        'type' => OutboundEmail::TYPE_CAR_PARK_TICKETS,
        'status' => OutboundEmail::STATUS_SENT,
        'to_email' => 'reader@example.test',
        'payload' => ['registration_ids' => [3], 'note' => null],
        'attempts' => 1,
        'sent_at' => now()->subMinute(),
        'provider_status' => OutboundEmail::PROVIDER_DELIVERED,
        'delivered_at' => now()->subMinute(),
    ]);

    $this->postJson('/webhooks/resend', [
        'type' => 'email.opened',
        'created_at' => now()->toIso8601String(),
        'data' => [
            'email_id' => 'opened-email-id-123',
            'to' => ['reader@example.test'],
        ],
    ])->assertOk();

    $outbound->refresh();
    expect($outbound->provider_status)->toBe(OutboundEmail::PROVIDER_OPENED)
        ->and($outbound->opened_at)->not->toBeNull()
        ->and($outbound->delivered_at)->not->toBeNull();
});

test('resend webhook marks matched outbound email as bounced', function () {
    Config::set('services.resend.webhook_secret', '');

    $outbound = OutboundEmail::query()->create([
        'type' => OutboundEmail::TYPE_CAR_PARK_TICKETS,
        'status' => OutboundEmail::STATUS_SENT,
        'to_email' => 'bounce@example.test',
        'payload' => ['registration_ids' => [9], 'note' => null],
        'attempts' => 1,
        'sent_at' => now()->subMinute(),
    ]);

    $payload = [
        'type' => 'email.bounced',
        'created_at' => now()->toIso8601String(),
        'data' => [
            'email_id' => '56761188-7520-42d8-8898-ff6fc54ce618',
            'to' => ['bounce@example.test'],
            'bounce' => [
                'type' => 'Permanent',
                'subType' => 'General',
                'message' => 'mailbox unavailable',
            ],
        ],
    ];

    $this->postJson('/webhooks/resend', $payload)
        ->assertOk();

    $outbound->refresh();
    expect($outbound->provider_status)->toBe(OutboundEmail::PROVIDER_BOUNCED)
        ->and($outbound->bounced_at)->not->toBeNull()
        ->and($outbound->provider_email_id)->toBe('56761188-7520-42d8-8898-ff6fc54ce618')
        ->and($outbound->provider_detail)->toContain('mailbox unavailable');

    expect(OutboundEmailEvent::query()->count())->toBe(1);
});

test('resend webhook marks matched outbound email as delivered', function () {
    Config::set('services.resend.webhook_secret', '');

    $outbound = OutboundEmail::query()->create([
        'type' => OutboundEmail::TYPE_CANCELLATION,
        'status' => OutboundEmail::STATUS_SENT,
        'to_email' => 'ok@example.test',
        'payload' => [
            'ticket_number' => '0001',
            'congregation' => 'Test',
            'driver_name' => 'Driver',
        ],
        'attempts' => 1,
        'sent_at' => now()->subMinute(),
    ]);

    app(ResendWebhookProcessor::class)->handle([
        'type' => 'email.delivered',
        'created_at' => now()->toIso8601String(),
        'data' => [
            'email_id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'to' => ['ok@example.test'],
        ],
    ], 'svix_test_1');

    $outbound->refresh();
    expect($outbound->provider_status)->toBe(OutboundEmail::PROVIDER_DELIVERED)
        ->and($outbound->delivered_at)->not->toBeNull();
});

test('resend webhook verifier accepts valid svix signature', function () {
    $secret = 'whsec_'.base64_encode('test-secret-bytes-here!!');
    $id = 'msg_test_123';
    $timestamp = (string) time();
    $payload = '{"type":"email.delivered","data":{}}';
    $secretBytes = base64_decode(substr($secret, 6), true);
    $signature = base64_encode(hash_hmac('sha256', $id.'.'.$timestamp.'.'.$payload, $secretBytes, true));

    ResendWebhookVerifier::verify($payload, [
        'svix-id' => $id,
        'svix-timestamp' => $timestamp,
        'svix-signature' => 'v1,'.$signature,
    ], $secret);

    expect(true)->toBeTrue();
});

test('resend webhook rejects invalid signature when secret configured', function () {
    Config::set('services.resend.webhook_secret', 'whsec_'.base64_encode('real-secret'));

    $this->postJson('/webhooks/resend', [
        'type' => 'email.delivered',
        'data' => ['to' => ['x@example.test']],
    ], [
        'svix-id' => 'msg_bad',
        'svix-timestamp' => (string) time(),
        'svix-signature' => 'v1,not-valid',
    ])->assertStatus(400);
});

test('quota block on primary leaves failover available', function () {
    config([
        'mail.transactional_primary' => 'smtp',
        'mail.transactional_failover' => 'brevo',
        'mail.mailers.smtp' => [
            'transport' => 'smtp',
            'host' => 'smtp.example.test',
            'port' => 587,
            'username' => 'resend',
            'password' => 'primary-secret',
        ],
        'mail.mailers.brevo' => [
            'transport' => 'smtp',
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'username' => 'brevo@example.test',
            'password' => 'failover-secret',
        ],
    ]);

    MailSendingQuota::clear();
    MailSendingQuota::markProviderExceeded(
        'smtp',
        new RuntimeException('550 You have reached your daily email sending quota.'),
    );

    expect(MailSendingQuota::isProviderBlocked('smtp'))->toBeTrue()
        ->and(MailSendingQuota::isProviderBlocked('brevo'))->toBeFalse()
        ->and(MailSendingQuota::isBlocked())->toBeFalse()
        ->and(MailSendingQuota::hasAvailableProvider())->toBeTrue();
});
