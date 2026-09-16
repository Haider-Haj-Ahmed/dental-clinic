<?php

namespace App\Jobs;

use App\Events\RecallOverdue;
use App\Models\Recall;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled: daily at 07:00.
 * Finds overdue pending/sent recalls not reminded in last 7 days.
 * Fires RecallOverdue event — listener notifies owner.
 * Updates last_reminder_sent_at and status to 'sent'.
 */
class SendRecallRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function handle(): void
    {
        $recalls = Recall::query()
            ->with('patient')
            ->whereIn('status', [Recall::STATUS_PENDING, Recall::STATUS_SENT])
            ->whereDate('due_date', '<', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('last_reminder_sent_at')
                  ->orWhere('last_reminder_sent_at', '<', now()->subDays(7));
            })
            ->get();

        if ($recalls->isEmpty()) {
            Log::info('SendRecallRemindersJob: no overdue recalls.');
            return;
        }

        foreach ($recalls as $recall) {
            event(new RecallOverdue($recall));
            $recall->update([
                'status'                => Recall::STATUS_SENT,
                'last_reminder_sent_at' => now(),
            ]);
        }

        Log::info("SendRecallRemindersJob: processed {$recalls->count()} overdue recall(s).");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendRecallRemindersJob failed', ['error' => $e->getMessage()]);
    }
}
