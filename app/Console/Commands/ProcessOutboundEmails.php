<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OutboundEmailProcessor;
use App\Support\MailSendingQuota;
use Illuminate\Console\Command;

class ProcessOutboundEmails extends Command
{
    protected $signature = 'mail:process-outbound {--limit=1 : Max pending emails to attempt}';

    protected $description = 'Send due outbound emails, deferring automatically when the provider quota is exhausted';

    public function handle(OutboundEmailProcessor $processor): int
    {
        if (MailSendingQuota::isBlocked()) {
            $this->warn('Mail quota is still blocked until '.MailSendingQuota::availableAt()->toDateTimeString());

            return self::SUCCESS;
        }

        $processed = $processor->processDue(max(1, (int) $this->option('limit')));
        $this->info("Processed {$processed} outbound email(s).");

        return self::SUCCESS;
    }
}
