<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTaskCommentRequest;
use App\Http\Resources\Api\V1\TaskCommentResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TaskCommentController extends Controller
{
    public function store(StoreTaskCommentRequest $request, Task $task): JsonResponse
    {
        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return (new TaskCommentResource($comment->load('author')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
