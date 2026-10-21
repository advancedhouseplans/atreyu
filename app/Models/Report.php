<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $occurrence_parent_id
 * @property string|null $fingerprint
 * @property string $source_slug
 * @property string $environment
 * @property string|null $reporter_email
 * @property string|null $reporter_name
 * @property string $type
 * @property string $severity
 * @property string $url
 * @property Carbon|null $archived_at
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
        'archived_at' => 'datetime',
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

    /** @return BelongsTo<Report, $this> */
    public function canonical(): BelongsTo
    {
        return $this->belongsTo(self::class, 'occurrence_parent_id');
    }

    /** @return HasMany<Report, $this> */
    public function occurrences(): HasMany
    {
        return $this->hasMany(self::class, 'occurrence_parent_id')->latest();
    }

    public function canonicalReport(): self
    {
        return $this->canonical()->first() ?? $this;
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
