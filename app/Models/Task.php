<?php

namespace App\Models;

use App\Enums\TaskDifficulty;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $parent_id
 * @property int|null $report_id
 * @property int|null $assignee_id
 * @property int|null $creator_id
 * @property TaskType $type
 * @property TaskStatus $status
 * @property int $position
 * @property TaskDifficulty|null $difficulty
 * @property TaskPriority $priority
 * @property Carbon|null $due_date
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $archived_at
 * @property-read string $identifier
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id', 'parent_id', 'report_id', 'assignee_id', 'creator_id', 'type',
        'status', 'position', 'difficulty', 'priority', 'due_date', 'title', 'description', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'status' => TaskStatus::class,
            'difficulty' => TaskDifficulty::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public static function nextPosition(TaskStatus $status, ?int $parentId = null): int
    {
        $lastPosition = static::where('status', $status)
            ->where('parent_id', $parentId)
            ->whereNull('archived_at')
            ->max('position');

        return $lastPosition === null ? 0 : $lastPosition + 1;
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Task, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Task, $this> */
    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /** @return BelongsTo<Report, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /** @return HasMany<TaskComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->oldest()->orderBy('id');
    }

    /** @return HasMany<TaskActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->oldest()->orderBy('id');
    }

    /** @return Attribute<non-falsy-string, never> */
    protected function identifier(): Attribute
    {
        return Attribute::get(fn (): string => 'AT-'.$this->getKey());
    }
}
