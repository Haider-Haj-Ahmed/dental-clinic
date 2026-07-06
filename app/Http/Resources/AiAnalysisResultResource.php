<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AiAnalysisResultResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'patient_id'    => $this->patient_id,
            'source_type'   => $this->source_type,
            'source_id'     => $this->source_id,
            'analysis_type' => $this->analysis_type,
            'ai_provider'   => $this->ai_provider,
            'ai_model'      => $this->ai_model,
            'input_summary' => $this->input_summary,
            'result'        => $this->result,
            'status'        => $this->status,
            'reviewer_notes'=> $this->reviewer_notes,
            'reviewed_at'   => $this->reviewed_at?->toIso8601String(),
            'requested_by'  => UserResource::make($this->whenLoaded('requestedBy')),
            'reviewed_by'   => UserResource::make($this->whenLoaded('reviewedBy')),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
