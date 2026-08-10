<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

final class TransactionalMail
{
    /**
     * Send via the first available provider (primary → failover → tertiary).
     *
     * @param  string|list<string>  $to
     * @return array{mailer: string}
     */
    public static function send(Mailable $mailable, string|array $to): array
    {
        $errors = [];

        foreach (MailSendingQuota::mailersInPreferenceOrder() as $mailer) {
            if (MailSendingQuota::isProviderBlocked($mailer)) {
                continue;
            }

            if (! MailSendingQuota::mailerConfigured($mailer)) {
                continue;
            }

            if ($mailer === 'brevo' && ! BrevoSendCredits::hasCredits()) {
                $e = new RuntimeException(
                    'Brevo daily/monthly send limit reached (account credits are 0).'
                );
                MailSendingQuota::markProviderExceeded('brevo', $e);
                $errors[] = $e;
                Log::warning('Mail provider quota exceeded; trying next if available', [
                    'mailer' => 'brevo',
                    'message' => $e->getMessage(),
                    'credits' => BrevoSendCredits::remaining(),
                ]);

                continue;
            }

            try {
                Mail::mailer($mailer)->to($to)->send($mailable);

                if ($mailer === 'brevo') {
                    // Force a fresh credit read soon after a send burst.
                    BrevoSendCredits::forgetCache();
                }

                Log::info('Transactional mail sent', ['mailer' => $mailer]);

                return ['mailer' => $mailer];
            } catch (Throwable $e) {
                if (MailSendingQuota::isExceeded($e)) {
                    MailSendingQuota::markProviderExceeded($mailer, $e);
                    $errors[] = $e;
                    Log::warning('Mail provider quota exceeded; trying next if available', [
                        'mailer' => $mailer,
                        'message' => $e->getMessage(),
                    ]);

                    continue;
                }

                throw $e;
            }
        }

        if ($errors !== []) {
            throw $errors[array_key_last($errors)];
        }

        throw new RuntimeException('No mail providers are configured or available.');
    }
}
