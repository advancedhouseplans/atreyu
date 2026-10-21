<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ReporterInstallation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReporterInstallation>
 */
class ReporterInstallationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Local development',
            'environment' => 'local',
            'key_id' => 'atry_'.Str::lower((string) Str::ulid()),
            'secret' => rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='),
            'endpoint' => fake()->url(),
        ];
    }
}
