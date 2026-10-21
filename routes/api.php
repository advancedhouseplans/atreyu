<?php

use App\Http\Controllers\Api\ReporterHeartbeatController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskCommentController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TaskPositionController;
use Illuminate\Support\Facades\Route;

Route::middleware('bug.token')->group(function () {
    Route::post('/reports', [ReportsController::class, 'store'])->name('api.reports.store');
});

Route::post('/reporter/heartbeat', ReporterHeartbeatController::class)
    ->middleware(['reporter.signature', 'throttle:30,1'])
    ->name('api.reporter.heartbeat');

Route::prefix('v1')->name('api.v1.')->middleware('auth:sanctum')->group(function () {
    Route::get('/me', MeController::class)->name('me');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::patch('/tasks/{task}/position', TaskPositionController::class)->name('tasks.position.update');
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
    Route::apiResource('tasks', TaskController::class)->only(['index', 'store', 'show', 'update']);
});
