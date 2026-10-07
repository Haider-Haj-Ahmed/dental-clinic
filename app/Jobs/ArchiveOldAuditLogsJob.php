<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ArchiveOldAuditLogsJob
 *
 * Scheduled: monthly on the 1st at 02:00.
 * Exports audit logs older than 1 year to a JSON file in storage,
 * then deletes the archived rows from the database.
 * Archive path: audit-archives/audit-logs-{YYYY-MM}.json
 */
class ArchiveOldAuditLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function handle(): void
    {
        $cutoff = now()->subYear();
        $label  = now()->subMonth()->format('Y-m');

        $logs = AuditLog::query()
            ->where('created_at', '<', $cutoff)
            ->orderBy('created_at')
            ->get();

        if ($logs->isEmpty()) {
            Log::info('ArchiveOldAuditLogsJob: no logs older than 1 year to archive.');
            return;
        }

        $filename = "audit-archives/audit-logs-{$label}.json";
        Storage::put($filename, $logs->toJson(JSON_PRETTY_PRINT));

        // Delete in chunks to avoid locking the table
        $logs->pluck('id')->chunk(500)->each(
            fn ($chunk) => AuditLog::whereIn('id', $chunk)->delete()
        );

        Log::info("ArchiveOldAuditLogsJob: archived {$logs->count()} logs to {$filename}.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ArchiveOldAuditLogsJob failed', ['error' => $e->getMessage()]);
    }
}
