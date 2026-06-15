<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Recall;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    // ── Dashboard KPIs ────────────────────────────────────────────────────────

    public function dashboardKpis(): array
    {
        $today     = today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd   = $today->copy()->endOfMonth();

        return [
            'today' => [
                'appointments_total'    => Appointment::whereDate('start_at', $today)->count(),
                'appointments_completed'=> Appointment::whereDate('start_at', $today)
                                            ->where('status', Appointment::STATUS_COMPLETED)->count(),
                'appointments_remaining'=> Appointment::whereDate('start_at', $today)
                                            ->whereIn('status', [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED])->count(),
            ],
            'month' => [
                'revenue_collected' => (float) Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount'),
                'invoices_created'  => Invoice::whereBetween('issued_at', [$monthStart, $monthEnd])
                                        ->whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID])->count(),
                'new_patients'      => Patient::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            ],
            'totals' => [
                'active_patients'   => Patient::where('is_active', true)->whereNull('deleted_at')->count(),
                'pending_recalls'   => Recall::where('status', Recall::STATUS_PENDING)
                                        ->whereDate('due_date', '<=', $today)->count(),
                'low_stock_items'   => InventoryItem::where('is_active', true)
                                        ->whereColumn('current_stock', '<=', 'reorder_level')->count(),
                'outstanding_balance' => (float) DB::table('invoices')
                                        ->whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID, Invoice::STATUS_PAID])
                                        ->sum(DB::raw('total - (SELECT COALESCE(SUM(amount),0) FROM payments WHERE payments.invoice_id = invoices.id)')),
            ],
        ];
    }

    // ── Appointments report ───────────────────────────────────────────────────

    public function appointmentsReport(array $filters): array
    {
        $from       = Carbon::parse($filters['date_from'] ?? now()->startOfMonth());
        $to         = Carbon::parse($filters['date_to']   ?? now()->endOfMonth());
        $providerId = $filters['provider_id'] ?? null;

        $base = Appointment::query()
            ->whereBetween('start_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($providerId, fn ($q) => $q->where('provider_id', $providerId));

        $byStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $byProvider = (clone $base)
            ->select('provider_id', DB::raw('COUNT(*) as count'))
            ->with('provider:id,name')
            ->groupBy('provider_id')
            ->get()
            ->map(fn ($row) => [
                'provider_id'   => $row->provider_id,
                'provider_name' => $row->provider?->name,
                'count'         => $row->count,
            ]);

        $byDay = (clone $base)
            ->select(DB::raw('DATE(start_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return [
            'period'      => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'total'       => (clone $base)->count(),
            'by_status'   => $byStatus,
            'by_provider' => $byProvider,
            'by_day'      => $byDay,
        ];
    }

    // ── Production report ─────────────────────────────────────────────────────

    public function productionReport(array $filters): array
    {
        $from       = Carbon::parse($filters['date_from'] ?? now()->startOfMonth());
        $to         = Carbon::parse($filters['date_to']   ?? now()->endOfMonth());
        $providerId = $filters['provider_id'] ?? null;

        $base = Invoice::query()
            ->whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID])
            ->whereBetween('issued_at', [$from, $to])
            ->when($providerId, fn ($q) => $q->where('provider_id', $providerId));

        $byProvider = (clone $base)
            ->select('provider_id', DB::raw('SUM(total) as total_billed'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('provider_id')
            ->get()
            ->map(fn ($row) => [
                'provider_id'    => $row->provider_id,
                'total_billed'   => (float) $row->total_billed,
                'invoice_count'  => $row->invoice_count,
            ]);

        $byMonth = (clone $base)
            ->select(
                DB::raw("DATE_FORMAT(issued_at, '%Y-%m') as month"),
                DB::raw('SUM(total) as total_billed')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_billed', 'month')
            ->map(fn ($v) => (float) $v);

        return [
            'period'      => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'total_billed'=> (float) (clone $base)->sum('total'),
            'by_provider' => $byProvider,
            'by_month'    => $byMonth,
        ];
    }

    // ── Collections report ────────────────────────────────────────────────────

    public function collectionsReport(array $filters): array
    {
        $from = Carbon::parse($filters['date_from'] ?? now()->startOfMonth());
        $to   = Carbon::parse($filters['date_to']   ?? now()->endOfMonth());

        $collected = Payment::whereBetween('paid_at', [$from, $to])->sum('amount');

        $billed = Invoice::whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID])
            ->whereBetween('issued_at', [$from, $to])
            ->sum('total');

        $byMethod = Payment::whereBetween('paid_at', [$from, $to])
            ->select('payment_method_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method_id')
            ->with('paymentMethod:id,name')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->paymentMethod?->name ?? 'Unspecified',
                'total'  => (float) $row->total,
                'count'  => $row->count,
            ]);

        $byDay = Payment::whereBetween('paid_at', [$from, $to])
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->map(fn ($v) => (float) $v);

        return [
            'period'              => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'total_collected'     => (float) $collected,
            'total_billed'        => (float) $billed,
            'collection_rate'     => $billed > 0 ? round(($collected / $billed) * 100, 2) : 0,
            'by_payment_method'   => $byMethod,
            'by_day'              => $byDay,
        ];
    }

    // ── Recall performance report ─────────────────────────────────────────────

    public function recallPerformanceReport(array $filters): array
    {
        $from = Carbon::parse($filters['date_from'] ?? now()->startOfMonth());
        $to   = Carbon::parse($filters['date_to']   ?? now()->endOfMonth());

        $base = Recall::whereBetween('due_date', [$from, $to]);

        $byStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $total      = (clone $base)->count();
        $booked     = (int) ($byStatus[Recall::STATUS_BOOKED]    ?? 0);
        $sent       = (int) ($byStatus[Recall::STATUS_SENT]      ?? 0);
        $dismissed  = (int) ($byStatus[Recall::STATUS_DISMISSED] ?? 0);
        $pending    = (int) ($byStatus[Recall::STATUS_PENDING]   ?? 0);

        return [
            'period'          => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'total'           => $total,
            'by_status'       => $byStatus,
            'conversion_rate' => $total > 0 ? round(($booked / $total) * 100, 2) : 0,
            'reminder_rate'   => $total > 0 ? round((($sent + $booked) / $total) * 100, 2) : 0,
        ];
    }

    // ── Inventory report ──────────────────────────────────────────────────────

    public function inventoryReport(array $filters): array
    {
        $categoryFilter = $filters['category'] ?? null;

        $items = InventoryItem::query()
            ->with('supplier:id,name')
            ->when($categoryFilter, fn ($q) => $q->where('category', $categoryFilter))
            ->where('is_active', true)
            ->get();

        $lowStock   = $items->filter(fn ($i) => $i->isLowStock());
        $totalValue = $items->sum(fn ($i) => $i->current_stock * $i->unit_cost);

        $byCategory = $items->groupBy('category')->map(fn ($group, $cat) => [
            'category'    => $cat,
            'item_count'  => $group->count(),
            'total_value' => (float) $group->sum(fn ($i) => $i->current_stock * $i->unit_cost),
            'low_stock'   => $group->filter(fn ($i) => $i->isLowStock())->count(),
        ])->values();

        return [
            'summary' => [
                'total_items'       => $items->count(),
                'low_stock_count'   => $lowStock->count(),
                'total_stock_value' => round((float) $totalValue, 2),
            ],
            'low_stock_items' => $lowStock->map(fn ($i) => [
                'id'            => $i->id,
                'name'          => $i->name,
                'sku'           => $i->sku,
                'current_stock' => $i->current_stock,
                'reorder_level' => $i->reorder_level,
                'supplier'      => $i->supplier?->name,
            ])->values(),
            'by_category' => $byCategory,
        ];
    }
}
