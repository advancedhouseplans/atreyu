<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportAttachment>
 */
class ReportAttachmentFactory extends Factory
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
            'disk' => 'local',
            'path' => 'reports/1/x.png',
            'mime' => 'image/png',
            'size' => 1024,
            'kind' => 'user_upload',
        ];
    }
}
