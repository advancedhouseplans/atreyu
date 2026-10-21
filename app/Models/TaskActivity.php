<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $task_id
 * @property int|null $actor_id
 * @property string $event
 * @property array<string, mixed>|null $data
 * @property Carbon|null $created_at
 */
class TaskActivity extends Model
{
    protected $fillable = ['task_id', 'actor_id', 'event', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function summary(): string
    {
        return match ($this->event) {
            'created' => 'created the task',
            'promoted' => 'created the task from a report',
            'status_changed' => 'moved the task from '.data_get($this->data, 'from_label').' to '.data_get($this->data, 'to_label'),
            'archived' => 'archived the task',
            'reopened' => 'reopened the task',
            'details_updated' => 'updated '.$this->updatedFieldsSummary(),
            default => str($this->event)->replace('_', ' ')->toString(),
        };
    }

    private function updatedFieldsSummary(): string
    {
        $fields = $this->data['fields'] ?? null;
        if (! is_array($fields)) {
            return 'task details';
        }

        $names = array_values(array_filter($fields, is_string(...)));

        return match (count($names)) {
            0 => 'task details',
            1 => $names[0],
            2 => implode(' and ', $names),
            default => implode(', ', array_slice($names, 0, -1)).' and '.$names[array_key_last($names)],
        };
    }
}
