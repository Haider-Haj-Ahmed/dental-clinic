<?php

namespace App\Jobs;

use App\Events\AiResultReady;
use App\Mail\AiResultPendingMail;
use App\Models\AiAnalysisResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ProcessAiAnalysisJob
 *
 * Moves AI API calls off the HTTP thread entirely.
 * Dispatched by AI controllers instead of calling AiService synchronously.
 * Calls AiService::processResult(), updates the result record,
 * then fires AiResultReady event + queues AiResultPendingMail.
 *
 * Usage:
 *   ProcessAiAnalysisJob::dispatch($result)->onQueue('default');
 */
class ProcessAiAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries  = 2;
    public int   $timeout = 120;
    public array $backoff = [60, 120];

    public function __construct(private readonly AiAnalysisResult $result) {}

    public function handle(): void
    {
        try {
            app(\App\Services\AiService::class)->processResult($this->result);

            $this->result->refresh()->load(['patient', 'requestedBy']);

            event(new AiResultReady($this->result));

            if ($this->result->requestedBy?->email) {
                Mail::to($this->result->requestedBy->email, $this->result->requestedBy->name)
                    ->queue(new AiResultPendingMail($this->result));
            }

            Log::info("ProcessAiAnalysisJob: result #{$this->result->id} processed.");

        } catch (\Throwable $e) {
            $this->result->update(['status' => AiAnalysisResult::STATUS_DISMISSED]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->result->update(['status' => AiAnalysisResult::STATUS_DISMISSED]);
        Log::error('ProcessAiAnalysisJob permanently failed', [
            'result_id' => $this->result->id,
            'error'     => $e->getMessage(),
        ]);
    }
}
