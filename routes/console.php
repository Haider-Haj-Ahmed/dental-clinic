<?php

use App\Jobs\CheckLowStockJob;
use App\Jobs\GenerateWeeklyReportJob;
use App\Jobs\PruneExpiredTokensJob;
use App\Jobs\SendAppointmentRemindersJob;
use App\Jobs\SendRecallRemindersJob;
use Illuminate\Support\Facades\Schedule;

/*
|──────────────────────────────────────────────────────────────────
| Crystalline Dental PMS — Task Scheduler
|──────────────────────────────────────────────────────────────────
| All jobs are dispatched to the queue — the scheduler only
| triggers them. Ensure queue:work is running via Supervisor.
|
| Production cron entry (or use Supervisor dental-scheduler):
|   * * * * * cd /var/www/dental-clinic && php artisan schedule:run >> /dev/null 2>&1
|──────────────────────────────────────────────────────────────────
*/

// 03:00 daily — prune expired Sanctum tokens (off-peak, no user impact)
Schedule::job(new PruneExpiredTokensJob)
    ->dailyAt('03:00')
    ->name('prune-expired-tokens')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Scheduled job failed: prune-expired-tokens'));

// 07:00 daily — process overdue recalls, fire RecallOverdue events
Schedule::job(new SendRecallRemindersJob)
    ->dailyAt('07:00')
    ->name('send-recall-reminders')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Scheduled job failed: send-recall-reminders'));

// 08:00 daily — remind staff of appointments in next 24h
Schedule::job(new SendAppointmentRemindersJob)
    ->dailyAt('08:00')
    ->name('send-appointment-reminders')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Scheduled job failed: send-appointment-reminders'));

// 08:30 daily — check inventory levels, fire LowStockAlert events
Schedule::job(new CheckLowStockJob)
    ->dailyAt('08:30')
    ->name('check-low-stock')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Scheduled job failed: check-low-stock'));

// 07:00 every Monday — weekly production report to owner
Schedule::job(new GenerateWeeklyReportJob)
    ->weeklyOn(1, '07:00')
    ->name('generate-weekly-report')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Scheduled job failed: generate-weekly-report'));
