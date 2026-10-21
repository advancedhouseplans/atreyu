<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class ReportSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_slug' => $this->source_slug,
            'environment' => $this->environment,
            'type' => $this->type,
            'severity' => $this->severity,
            'reporter_name' => $this->reporter_name,
            'reporter_email' => $this->reporter_email,
            'url' => $this->url,
        ];
    }
}
