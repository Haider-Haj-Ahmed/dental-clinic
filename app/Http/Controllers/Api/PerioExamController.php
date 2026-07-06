<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePerioExamRequest;
use App\Http\Requests\StorePerioMeasureRequest;
use App\Http\Resources\PerioExamResource;
use App\Http\Resources\PerioMeasureResource;
use App\Models\PerioExam;
use App\Models\PerioMeasure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PerioExamController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PerioExam::class, 'perio_exam');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $exams = PerioExam::query()
            ->withCount('measures')
            ->with(['patient', 'provider'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->integer('provider_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('exam_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('exam_date', '<=', $request->date('date_to')))
            ->orderBy('exam_date', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PerioExamResource::collection($exams);
    }

    public function store(StorePerioExamRequest $request): PerioExamResource
    {
        $exam = DB::transaction(function () use ($request) {
            $exam = PerioExam::query()->create($request->safe()->except('measures'));

            foreach ($request->input('measures', []) as $measure) {
                $exam->measures()->create($measure);
            }

            return $exam;
        });

        return PerioExamResource::make($exam->loadCount('measures')->load(['patient', 'provider', 'measures']));
    }

    public function show(PerioExam $perioExam): PerioExamResource
    {
        return PerioExamResource::make(
            $perioExam->loadCount('measures')->load(['patient', 'provider', 'measures'])
        );
    }

    public function update(Request $request, PerioExam $perioExam): PerioExamResource
    {
        $request->validate([
            'exam_date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'notes'     => ['sometimes', 'nullable', 'string'],
        ]);

        $perioExam->update($request->only(['exam_date', 'notes']));

        return PerioExamResource::make($perioExam->refresh()->loadCount('measures')->load(['patient', 'provider']));
    }

    public function destroy(PerioExam $perioExam): Response
    {
        $perioExam->delete();

        return $this->noContentResponse();
    }

    /** POST /perio-exams/{perioExam}/measures */
    public function storeMeasure(StorePerioMeasureRequest $request, PerioExam $perioExam): PerioMeasureResource
    {
        $this->authorize('update', $perioExam);

        $measure = $perioExam->measures()->create($request->validated());

        return PerioMeasureResource::make($measure);
    }

    /** DELETE /perio-exams/{perioExam}/measures/{measure} */
    public function destroyMeasure(PerioExam $perioExam, PerioMeasure $measure): Response
    {
        $this->authorize('update', $perioExam);
        abort_if($measure->perio_exam_id !== $perioExam->id, 404);

        $measure->delete();

        return $this->noContentResponse();
    }
}
