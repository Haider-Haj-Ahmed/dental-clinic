<?php

namespace App\Mail;

use App\Models\AiAnalysisResult;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AiResultPendingMail extends BaseMail
{
    public function __construct(private readonly AiAnalysisResult $result) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AI Analysis Ready for Review',
        );
    }

    public function content(): Content
    {
        $typeLabel = match ($this->result->analysis_type) {
            'soap_suggestion'         => 'SOAP Note Suggestion',
            'xray_analysis'           => 'X-Ray Analysis',
            'prescription_suggestion' => 'Prescription Suggestion',
            'perio_risk'              => 'Periodontal Risk Score',
            default                   => 'AI Analysis',
        };

        return new Content(
            view: 'emails.clinical.ai-result-pending',
            with: [
                'result'      => $this->result,
                'typeLabel'   => $typeLabel,
                'patientName' => $this->result->patient->first_name . ' ' . $this->result->patient->last_name,
                'reviewUrl'   => config('app.url') . '/dashboard/ai/' . $this->result->id,
                'branding'    => $this->branding(),
            ],
        );
    }
}
