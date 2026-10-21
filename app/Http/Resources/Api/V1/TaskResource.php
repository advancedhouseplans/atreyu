<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class TaskResource extends JsonResource
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
            'identifier' => $this->identifier,
            'project_id' => $this->project_id,
            'parent_id' => $this->parent_id,
            'report_id' => $this->report_id,
            'assignee_id' => $this->assignee_id,
            'creator_id' => $this->creator_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'position' => $this->position,
            'difficulty' => $this->difficulty?->value,
            'difficulty_label' => $this->difficulty?->label(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'due_date' => $this->due_date?->toDateString(),
            'title' => $this->title,
            'description' => $this->description,
            'comments_count' => $this->whenCounted('comments'),
            'subtasks_count' => $this->whenCounted('subtasks'),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'report' => new ReportSummaryResource($this->whenLoaded('report')),
            'comments' => TaskCommentResource::collection($this->whenLoaded('comments')),
            'activities' => TaskActivityResource::collection($this->whenLoaded('activities')),
            'archived_at' => $this->archived_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
