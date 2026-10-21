<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns 401 when no token is provided', function () {
    $this->getJson('/api/v1/projects')->assertUnauthorized();
});

it('returns projects alphabetically with task counts', function () {
    Sanctum::actingAs(User::factory()->create());
    $zebra = Project::factory()->create(['name' => 'Zebra', 'slug' => 'zebra']);
    $atreyu = Project::factory()->create(['name' => 'Atreyu', 'slug' => 'atreyu']);
    Task::factory()->count(2)->for($atreyu)->create();
    Task::factory()->for($zebra)->create();

    $this->getJson('/api/v1/projects')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Atreyu')
        ->assertJsonPath('data.0.tasks_count', 2)
        ->assertJsonPath('data.1.name', 'Zebra')
        ->assertJsonPath('data.1.tasks_count', 1);
});
