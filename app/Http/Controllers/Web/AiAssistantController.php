<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiResult;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Web\AiAssistantController
 *
 * Renders the AI Assistant dashboard views.
 * Does NOT touch any Api\ controller or modify API response flow.
 *
 * Routes (web.php):
 *   GET    /dashboard/ai              → index
 *   GET    /dashboard/ai/{result}     → show   (HTMX panel swap)
 *   PATCH  /dashboard/ai/{result}/accept  → accept
 *   PATCH  /dashboard/ai/{result}/dismiss → dismiss
 */
class AiAssistantController extends Controller
{
    /* ── Type → result_type column value map ─────────────── */
    private const TYPE_MAP = [
        'xray'         => 'xray',
        'soap'         => 'soap',
        'prescription' => 'prescription',
        'perio'        => 'perio',
    ];

    /**
     * Main AI assistant listing page.
     */
    public function index(Request $request): View
    {
        $type   = $request->query('type', 'all');
        $status = $request->query('status', 'all');

        /* ── Base query ─────────────────────────────────── */
        $query = AiResult::with('patient')
            ->latest();

        if ($type !== 'all' && isset(self::TYPE_MAP[$type])) {
            $query->where('result_type', self::TYPE_MAP[$type]);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $results = $query->paginate(12)->withQueryString();

        /* ── Counts per type (for filter pills) ─────────── */
        $counts = [
            'all'          => AiResult::count(),
            'xray'         => AiResult::where('result_type', 'xray')->count(),
            'soap'         => AiResult::where('result_type', 'soap')->count(),
            'prescription' => AiResult::where('result_type', 'prescription')->count(),
            'perio'        => AiResult::where('result_type', 'perio')->count(),
        ];

        /* ── Pre-select first result for detail panel ───── */
        $selected = $results->first()?->load('patient', 'encounter');

        return view('web.ai.index', [
            'results'      => $results,
            'activeType'   => $type,
            'activeStatus' => $status,
            'selected'     => $selected,
            'counts'       => $counts,
        ]);
    }

    /**
     * Return only the detail panel partial (HTMX swap target).
     */
    public function show(AiResult $result): View
    {
        $result->load('patient', 'encounter');

        /* If request came from HTMX, return the partial only */
        if (request()->header('HX-Request')) {
            return view('web.ai._detail', compact('result'));
        }

        /* Full page fallback (direct URL access) */
        return redirect()->route('web.ai', ['selected' => $result->id]);
    }

    /**
     * Accept a result — apply to encounter.
     */
    public function accept(Request $request, AiResult $result)
    {
        $result->update(['status' => 'accepted']);

        if ($request->header('HX-Request')) {
            $result->load('patient', 'encounter');
            return view('web.ai._detail', compact('result'));
        }

        return back()->with('success', 'AI suggestion applied to encounter.');
    }

    /**
     * Dismiss a result.
     */
    public function dismiss(Request $request, AiResult $result)
    {
        $result->update(['status' => 'dismissed']);

        if ($request->header('HX-Request')) {
            $result->load('patient', 'encounter');
            return view('web.ai._detail', compact('result'));
        }

        return back()->with('success', 'AI suggestion dismissed.');
    }
}
