<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * HealthController
 *
 * GET /api/v1/health
 *
 * Returns the operational status of all critical subsystems.
 * Public endpoint — no authentication required.
 * Used by uptime monitors and load balancers.
 *
 * HTTP 200 — all systems healthy
 * HTTP 503 — one or more systems degraded
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks  = [];
        $healthy = true;

        // ── Database ─────────────────────────────────────────────
        try {
            DB::select('SELECT 1');
            $checks['database'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'error', 'message' => 'Cannot connect to database'];
            $healthy = false;
        }

        // ── Cache ─────────────────────────────────────────────────
        try {
            $key = 'health_check_' . now()->timestamp;
            Cache::put($key, true, 5);
            Cache::forget($key);
            $checks['cache'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'error', 'message' => 'Cache not writable'];
            $healthy = false;
        }

        // ── Queue (jobs table) ────────────────────────────────────
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();
            $checks['queue'] = [
                'status'        => 'ok',
                'pending_jobs'  => $pending,
                'failed_jobs'   => $failed,
            ];
            if ($failed > 10) {
                $checks['queue']['status'] = 'degraded';
                $checks['queue']['message'] = 'High failed job count';
            }
        } catch (\Throwable $e) {
            $checks['queue'] = ['status' => 'error', 'message' => 'Cannot read jobs table'];
            $healthy = false;
        }

        // ── Storage ───────────────────────────────────────────────
        try {
            $testFile = 'health_check_' . now()->timestamp . '.tmp';
            Storage::put($testFile, 'ok');
            Storage::delete($testFile);
            $checks['storage'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['storage'] = ['status' => 'error', 'message' => 'Storage not writable'];
            $healthy = false;
        }

        // ── Clinic settings (sanity check) ────────────────────────
        try {
            $settings = ClinicSetting::instance();
            $checks['settings'] = [
                'status'      => 'ok',
                'clinic_name' => $settings->clinic_name,
                'timezone'    => $settings->timezone,
            ];
        } catch (\Throwable $e) {
            $checks['settings'] = ['status' => 'error', 'message' => 'Cannot load clinic settings'];
            $healthy = false;
        }

        return response()->json([
            'status'    => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', '3.0.0'),
            'checks'    => $checks,
        ], $healthy ? 200 : 503);
    }
}
