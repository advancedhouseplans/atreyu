<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportNote>
 */
class ReportNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'user_id' => User::factory(),
            'body' => $this->faker->sentence(),
        ];
    }
}
