<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $repository_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'repository_url'];

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'source_slug', 'slug');
    }

    /** @return HasOne<ReporterInstallation, $this> */
    public function connection(): HasOne
    {
        return $this->hasOne(ReporterInstallation::class);
    }

    public function repositoryLabel(): ?string
    {
        if (! $this->repository_url) {
            return null;
        }

        $path = trim((string) parse_url($this->repository_url, PHP_URL_PATH), '/');

        return $path !== '' ? str($path)->beforeLast('.git')->toString() : $this->repository_url;
    }
}
