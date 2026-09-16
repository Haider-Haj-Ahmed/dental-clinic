<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled: daily at 03:00 (off-peak).
 * Deletes Sanctum tokens whose expires_at has passed.
 * Sanctum does not auto-prune expired tokens.
 */
class PruneExpiredTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function handle(): void
    {
        $deleted = DB::table('personal_access_tokens')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        Log::info("PruneExpiredTokensJob: deleted {$deleted} expired token(s).");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('PruneExpiredTokensJob failed', ['error' => $e->getMessage()]);
    }
}
