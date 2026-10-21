<?php

namespace App\Actions;

use App\Enums\TaskDifficulty;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromoteReportToTask
{
    public function handle(
        Report $report,
        User $actor,
        ?Project $project = null,
        ?User $assignee = null,
        ?TaskType $type = null,
        ?TaskDifficulty $difficulty = null,
        ?string $title = null,
    ): Task {
        return DB::transaction(function () use ($report, $actor, $project, $assignee, $type, $difficulty, $title): Task {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->id);
            $existingTask = $lockedReport->task()->first();

            if ($existingTask) {
                return $existingTask;
            }

            $project ??= Project::firstOrCreate(
                ['slug' => $lockedReport->source_slug],
                [
                    'name' => config("bug-sources.{$lockedReport->source_slug}.name", Str::headline($lockedReport->source_slug)),
                    'description' => 'Created from incoming reports.',
                ],
            );

            $task = Task::create([
                'project_id' => $project->id,
                'report_id' => $lockedReport->id,
                'assignee_id' => $assignee?->id,
                'creator_id' => $actor->id,
                'type' => $type ?? ($lockedReport->type === 'bug' ? TaskType::Bug : TaskType::Build),
                'status' => TaskStatus::Backlog,
                'position' => Task::nextPosition(TaskStatus::Backlog),
                'difficulty' => $difficulty,
                'priority' => match ($lockedReport->severity) {
                    'blocker' => TaskPriority::Urgent,
                    'high' => TaskPriority::High,
                    'low' => TaskPriority::Low,
                    default => TaskPriority::Normal,
                },
                'title' => filled($title) ? trim($title) : $lockedReport->title,
                'description' => $lockedReport->description,
            ]);

            $lockedReport->update(['status' => 'in_progress']);
            $report->setRawAttributes($lockedReport->getAttributes(), true);
            app(RecordTaskActivity::class)->handle($task, 'promoted', $actor, [
                'report_id' => $lockedReport->id,
            ]);

            return $task->load(['project', 'assignee', 'report']);
        });
    }
}
