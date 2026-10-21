<?php

use App\Actions\PromoteReportToTask;
use App\Enums\TaskDifficulty;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Support\Facades\Notification;

it('promotes a report into one linked task and source project', function () {
    $user = User::factory()->create();
    $report = Report::factory()->create([
        'source_slug' => 'takeoff',
        'type' => 'feature',
        'title' => 'Add plan comparison',
        'description' => 'Compare two estimates side by side.',
        'severity' => 'high',
    ]);

    $firstTask = app(PromoteReportToTask::class)->handle($report, $user);
    $secondTask = app(PromoteReportToTask::class)->handle($report, $user);

    expect($secondTask->is($firstTask))->toBeTrue()
        ->and($firstTask->type)->toBe(TaskType::Build)
        ->and($firstTask->status)->toBe(TaskStatus::Backlog)
        ->and($firstTask->priority)->toBe(TaskPriority::High)
        ->and($firstTask->project->name)->toBe('Takeoff');

    $this->assertDatabaseCount('tasks', 1);
    $this->assertDatabaseHas('tasks', [
        'report_id' => $report->id,
        'creator_id' => $user->id,
        'title' => 'Add plan comparison',
    ]);
    expect($report->refresh()->archived_at)->toBeNull();
    $this->assertDatabaseHas('task_activities', [
        'task_id' => $firstTask->id,
        'actor_id' => $user->id,
        'event' => 'promoted',
    ]);
});

it('uses the project, owner, type, difficulty, and title chosen during triage', function () {
    Notification::fake();
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();
    $report = Report::factory()->create([
        'source_slug' => 'another-app',
        'type' => 'feature',
        'title' => 'Original title',
    ]);

    $task = app(PromoteReportToTask::class)->handle(
        report: $report,
        actor: $actor,
        project: $project,
        assignee: $assignee,
        type: TaskType::Bug,
        difficulty: TaskDifficulty::Four,
        title: 'Checkout fails after login',
    );

    expect($task->project->is($project))->toBeTrue()
        ->and($task->assignee->is($assignee))->toBeTrue()
        ->and($task->type)->toBe(TaskType::Bug)
        ->and($task->difficulty)->toBe(TaskDifficulty::Four)
        ->and($task->title)->toBe('Checkout fails after login');
    $this->assertDatabaseMissing('projects', ['slug' => 'another-app']);
    Notification::assertSentTo($assignee, TaskAssigned::class);
});
