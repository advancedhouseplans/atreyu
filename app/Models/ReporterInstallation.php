<?php

namespace App\Models;

use Database\Factories\ReporterInstallationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $environment
 * @property string $key_id
 * @property string $secret
 * @property string|null $endpoint
 * @property array<int, string>|null $allowed_origins
 * @property string|null $package_version
 * @property string|null $app_version
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $revoked_at
 * @property Project $project
 */
class ReporterInstallation extends Model
{
    /** @use HasFactory<ReporterInstallationFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id', 'created_by', 'name', 'environment', 'key_id', 'secret',
        'endpoint', 'allowed_origins', 'package_version', 'app_version',
        'last_seen_at', 'revoked_at',
    ];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'allowed_origins' => 'array',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
