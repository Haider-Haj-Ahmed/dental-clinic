<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorkingHoursRequest;
use App\Models\WorkingHour;
use Illuminate\Http\JsonResponse;

/**
 * WorkingHourController
 *
 * Routes:
 *   GET /api/v1/working-hours        → index  (all authenticated)
 *   PUT /api/v1/working-hours        → update (owner only)
 */
class WorkingHourController extends Controller
{
    /**
     * Return all 7 days' working hours.
     * Accessible by all authenticated users — needed by scheduling UI.
     */
    public function index(): JsonResponse
    {
        $hours = WorkingHour::orderBy('day_of_week')->get()
            ->map(fn ($h) => [
                'day_of_week' => $h->day_of_week,
                'day_name'    => $h->day_name,
                'is_closed'   => $h->is_closed,
                'open_time'   => $h->is_closed ? null : $h->open_time,
                'close_time'  => $h->is_closed ? null : $h->close_time,
            ]);

        return response()->json(['data' => $hours]);
    }

    /**
     * Replace working hours.
     * Accepts an array of day entries — can update all 7 or a subset.
     * Owner only.
     */
    public function update(UpdateWorkingHoursRequest $request): JsonResponse
    {
        foreach ($request->validated('hours') as $entry) {
            WorkingHour::updateOrCreate(
                ['day_of_week' => $entry['day_of_week']],
                [
                    'is_closed'  => $entry['is_closed'],
                    'open_time'  => $entry['is_closed'] ? null : $entry['open_time'],
                    'close_time' => $entry['is_closed'] ? null : $entry['close_time'],
                ]
            );
        }

        $hours = WorkingHour::orderBy('day_of_week')->get()
            ->map(fn ($h) => [
                'day_of_week' => $h->day_of_week,
                'day_name'    => $h->day_name,
                'is_closed'   => $h->is_closed,
                'open_time'   => $h->is_closed ? null : $h->open_time,
                'close_time'  => $h->is_closed ? null : $h->close_time,
            ]);

        return response()->json([
            'message' => 'Working hours updated.',
            'data'    => $hours,
        ]);
    }
}
