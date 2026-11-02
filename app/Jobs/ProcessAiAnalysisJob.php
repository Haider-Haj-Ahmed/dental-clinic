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
        // NOTE: AiService::processResult() does not exist.
        // AI analysis is currently performed synchronously in each controller method
        // (analyzeDocument, suggestSoap, suggestPrescription, scorePerioRisk, prioritiseRecalls).
        // This job is reserved for Phase 5 async processing — wire it up then.
        // For now, log and exit gracefully to avoid queue failures.
        Log::warning("ProcessAiAnalysisJob dispatched but AI processing is synchronous. Result #{$this->result->id} was already processed.");
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
