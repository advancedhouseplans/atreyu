<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $source_slug
 * @property string $environment
 * @property string|null $reporter_email
 * @property string|null $reporter_name
 * @property string $type
 * @property string $severity
 * @property string $url
 * @property int|null $assignee_id
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'laravel_context' => 'array',
        'client_events' => 'array',
        'network_log' => 'array',
        'dpr' => 'float',
    ];

    /** @return HasMany<ReportNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(ReportNote::class);
    }

    /** @return HasMany<ReportAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return BelongsTo<ReporterInstallation, $this> */
    public function reporterInstallation(): BelongsTo
    {
        return $this->belongsTo(ReporterInstallation::class);
    }

    /** @return HasOne<Task, $this> */
    public function task(): HasOne
    {
        return $this->hasOne(Task::class);
    }
}
