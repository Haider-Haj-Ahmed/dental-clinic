<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerioMeasureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'perio_exam_id'     => $this->perio_exam_id,
            'tooth_number'      => $this->tooth_number,
            'site'              => $this->site,
            'probing_depth'     => $this->probing_depth,
            'recession'         => $this->recession,
            'bleeding_on_probe' => $this->bleeding_on_probe,
            'furcation'         => $this->furcation,
            'mobility'          => $this->mobility,
            'suppuration'       => $this->suppuration,
        ];
    }
}
