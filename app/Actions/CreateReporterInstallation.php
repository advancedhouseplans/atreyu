<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\ReporterInstallation;
use App\Models\User;
use App\Support\ReporterCredential;
use Illuminate\Support\Str;

class CreateReporterInstallation
{
    /** @return array{installation: ReporterInstallation, credential: string} */
    public function handle(Project $project, User $actor, string $name, string $environment): array
    {
        $keyId = 'atry_'.Str::lower((string) Str::ulid());
        $secret = ReporterCredential::generateSecret();
        $installation = $project->connection()->create([
            'created_by' => $actor->id,
            'name' => $name,
            'environment' => $environment,
            'key_id' => $keyId,
            'secret' => $secret,
        ]);

        return [
            'installation' => $installation,
            'credential' => ReporterCredential::format($keyId, $secret),
        ];
    }

    public function rotate(ReporterInstallation $installation): string
    {
        $secret = ReporterCredential::generateSecret();
        $installation->update(['secret' => $secret, 'revoked_at' => null]);

        return ReporterCredential::format($installation->key_id, $secret);
    }
}
