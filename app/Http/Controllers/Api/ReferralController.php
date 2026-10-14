<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReferralRequest;
use App\Models\ClinicSetting;
use App\Models\Referral;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReferralController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $referrals = Referral::query()
            ->with(['patient', 'referringProvider'])
            ->when($request->filled('patient_id'),  fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('referring_provider_id', $request->integer('provider_id')))
            ->when($request->filled('urgency'),     fn ($q) => $q->where('urgency', $request->string('urgency')))
            ->orderBy(
                $this->sortBy($request, ['referral_date', 'created_at', 'urgency'], 'referral_date'),
                $this->sortDir($request)
            )
            ->paginate($this->perPage($request))
            ->withQueryString();

        return response()->json($referrals);
    }

    public function store(StoreReferralRequest $request): JsonResponse
    {
        $referral = Referral::create(array_merge(
            $request->validated(),
            ['created_by' => $request->user()->id]
        ));

        return response()->json([
            'message' => 'Referral created.',
            'data'    => $referral->load(['patient', 'referringProvider']),
        ], 201);
    }

    public function show(Referral $referral): JsonResponse
    {
        return response()->json([
            'data' => $referral->load(['patient', 'referringProvider', 'encounter']),
        ]);
    }

    public function destroy(Referral $referral): JsonResponse
    {
        $referral->delete();
        return response()->json(['message' => 'Referral deleted.']);
    }

    public function pdf(Referral $referral): Response
    {
        $referral->load(['patient', 'referringProvider', 'encounter']);
        $settings = ClinicSetting::instance();

        $pdf = Pdf::loadView('pdf.referral-letter', compact('referral', 'settings'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);

        $referral->update(['is_printed' => true, 'printed_at' => now()]);

        return $pdf->stream('referral-' . str_pad($referral->id, 5, '0', STR_PAD_LEFT) . '.pdf');
    }
}
