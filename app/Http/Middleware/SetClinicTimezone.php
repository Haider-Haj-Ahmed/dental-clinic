<?php

namespace App\Http\Middleware;

use App\Models\ClinicSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetClinicTimezone
 *
 * Sets the application timezone per-request from ClinicSetting.
 * This means all Carbon::now() calls and datetime formatting
 * throughout the request lifecycle use the clinic's configured timezone.
 *
 * Applied to the api middleware group in bootstrap/app.php.
 *
 * The DB stores all timestamps in UTC (MySQL default).
 * This middleware converts display output to clinic local time.
 * When the clinic timezone is Asia/Damascus (GMT+3):
 *   UTC 06:00 → displayed as 09:00 local time
 */
class SetClinicTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $timezone = ClinicSetting::instance()->timezone ?? config('app.timezone', 'UTC');
            App::setLocale(ClinicSetting::instance()->language ?? config('app.locale', 'en'));
            Carbon::setTestNow(null);  // ensure fresh now()
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        } catch (\Throwable) {
            // If settings table doesn't exist yet (e.g. fresh install), use config default
        }

        return $next($request);
    }
}
