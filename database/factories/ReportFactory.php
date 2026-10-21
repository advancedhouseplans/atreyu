<?php

namespace Database\Factories;

use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_slug' => 'ahp',
            'environment' => 'production',
            'reporter_email' => $this->faker->safeEmail(),
            'reporter_name' => $this->faker->name(),
            'type' => 'bug',
            'severity' => 'med',
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'url' => 'https://ahp.test/plans/1',
            'user_agent' => 'Mozilla/5.0',
        ];
    }
}
