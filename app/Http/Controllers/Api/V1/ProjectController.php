<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProjectResource::collection(
            Project::query()
                ->withCount([
                    'tasks' => fn ($query) => $query->whereNull('archived_at'),
                ])
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
        );
    }
}
