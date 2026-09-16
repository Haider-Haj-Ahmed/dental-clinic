<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Recall;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled: every Monday at 07:00.
 * Aggregates previous week metrics.
 * Sends in-app notification to clinic owner.
 * Future: attach PDF report via email.
 */
class GenerateWeeklyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function handle(): void
    {
        $weekStart = now()->subWeek()->startOfWeek();
        $weekEnd   = now()->subWeek()->endOfWeek();

        $totalAppointments = Appointment::query()
            ->whereBetween('start_at', [$weekStart, $weekEnd])
            ->count();

        $completedAppointments = Appointment::query()
            ->where('status', Appointment::STATUS_COMPLETED)
            ->whereBetween('start_at', [$weekStart, $weekEnd])
            ->count();

        $totalRevenue = Payment::query()
            ->whereBetween('paid_at', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('amount');

        $overdueRecalls = Recall::query()
            ->whereIn('status', [Recall::STATUS_PENDING, Recall::STATUS_SENT])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        $weekLabel = $weekStart->format('d M') . ' – ' . $weekEnd->format('d M Y');
        $body      = "{$completedAppointments}/{$totalAppointments} appointments · "
                   . number_format($totalRevenue) . " SYP collected · {$overdueRecalls} overdue recalls";

        $owner = User::where('role', User::ROLE_OWNER)->first();
        $owner?->notify(new InAppNotification(
            type:  'report.weekly',
            title: "Weekly report — {$weekLabel}",
            body:  $body,
            data:  [
                'week_start'             => $weekStart->toDateString(),
                'week_end'               => $weekEnd->toDateString(),
                'total_appointments'     => $totalAppointments,
                'completed_appointments' => $completedAppointments,
                'total_revenue'          => $totalRevenue,
                'overdue_recalls'        => $overdueRecalls,
            ],
            url:   '/dashboard/reports',
        ));

        Log::info("GenerateWeeklyReportJob: report generated for {$weekLabel}.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateWeeklyReportJob failed', ['error' => $e->getMessage()]);
    }
}
