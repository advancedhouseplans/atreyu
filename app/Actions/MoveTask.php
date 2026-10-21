<?php

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MoveTask
{
    public function handle(Task $task, TaskStatus $destinationStatus, ?int $position = null, ?User $actor = null): void
    {
        DB::transaction(function () use ($task, $destinationStatus, $position, $actor): void {
            $sourceStatus = $task->status;
            $destinationTaskIds = Task::query()
                ->where('status', $destinationStatus)
                ->whereNull('archived_at')
                ->whereKeyNot($task->id)
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $destinationPosition = min(max($position ?? count($destinationTaskIds), 0), count($destinationTaskIds));
            array_splice($destinationTaskIds, $destinationPosition, 0, [$task->id]);

            foreach ($destinationTaskIds as $index => $taskId) {
                Task::whereKey($taskId)->update([
                    'status' => $destinationStatus,
                    'position' => $index,
                ]);
            }

            if ($sourceStatus !== $destinationStatus) {
                $this->reorder($sourceStatus);
            }

            $task->refresh();

            if ($sourceStatus !== $destinationStatus) {
                app(RecordTaskActivity::class)->handle($task, 'status_changed', $actor, [
                    'from' => $sourceStatus->value,
                    'from_label' => $sourceStatus->label(),
                    'to' => $destinationStatus->value,
                    'to_label' => $destinationStatus->label(),
                ]);
            }
        });
    }

    private function reorder(TaskStatus $status): void
    {
        Task::query()
            ->where('status', $status)
            ->whereNull('archived_at')
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->each(fn (int $taskId, int $position) => Task::whereKey($taskId)->update(['position' => $position]));
    }
}
