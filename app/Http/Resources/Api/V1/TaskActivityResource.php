<?php

namespace App\Http\Resources\Api\V1;

use App\Models\TaskActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskActivity */
class TaskActivityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'summary' => $this->summary(),
            'data' => $this->data,
            'actor' => new UserResource($this->whenLoaded('actor')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
