<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OutboundEmail;
use App\Models\ParkingRegistration;
use App\Services\OutboundEmailProcessor;
use App\Support\MailSendingQuota;
use Illuminate\Console\Command;

class RequeueQuotaFailedEmails extends Command
{
    protected $signature = 'mail:requeue-quota-failures
        {--log= : Optional path to laravel.log to mine failed deferred sends}
        {--dry-run : Show what would be queued without writing}';

    protected $description = 'Re-queue car park ticket emails that failed earlier due to provider quota limits';

    public function handle(OutboundEmailProcessor $processor): int
    {
        $logPath = $this->option('log') ?: storage_path('logs/laravel.log');
        $dryRun = (bool) $this->option('dry-run');
        $availableAt = MailSendingQuota::isBlocked() ? MailSendingQuota::availableAt() : null;

        $pairs = $this->pairsFromLog($logPath);
        $queued = 0;
        $skipped = 0;

        foreach ($pairs as $pair) {
            $ids = $pair['ids'];
            $to = $pair['to'];

            $existingRegs = ParkingRegistration::query()
                ->whereIn('id', $ids)
                ->pluck('id')
                ->all();

            if ($existingRegs === []) {
                $skipped++;
                $this->line('skip missing regs ['.implode(',', $ids)."] → {$to}");

                continue;
            }

            if ($dryRun) {
                $this->line('dry-run queue ['.implode(',', $existingRegs)."] → {$to}");
                $queued++;

                continue;
            }

            $processor->enqueue(
                OutboundEmail::TYPE_CAR_PARK_TICKETS,
                $to,
                [
                    'registration_ids' => array_values(array_map('intval', $existingRegs)),
                    'note' => null,
                ],
                $availableAt,
            );
            $queued++;
            $this->info('queued ['.implode(',', $existingRegs)."] → {$to}");
        }

        $this->info("Done. queued={$queued} skipped={$skipped} from_log=".count($pairs));

        return self::SUCCESS;
    }

    /**
     * @return list<array{ids: list<int>, to: string}>
     */
    private function pairsFromLog(string $logPath): array
    {
        if (! is_file($logPath)) {
            $this->warn("Log not found: {$logPath}");

            return [];
        }

        $log = (string) file_get_contents($logPath);
        preg_match_all(
            '/Deferred car park ticket email failed \{"to":"([^"]+)","registration_ids":\[([^\]]*)\]/',
            $log,
            $matches,
            PREG_SET_ORDER,
        );

        $pairs = [];
        foreach ($matches as $match) {
            $to = strtolower(trim($match[1]));
            $ids = array_values(array_filter(array_map(
                static fn (string $part): int => (int) trim($part),
                $match[2] === '' ? [] : explode(',', $match[2]),
            ), static fn (int $id): bool => $id > 0));

            if ($to === '' || $ids === []) {
                continue;
            }

            $key = implode(',', $ids).'|'.$to;
            $pairs[$key] = ['ids' => $ids, 'to' => $to];
        }

        return array_values($pairs);
    }
}
