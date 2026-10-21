<?php

use App\Livewire\CommandPalette;
use App\Livewire\Tasks\Index;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('searches reports, tasks and projects', function () {
    Report::factory()->create(['title' => 'Checkout 500']);
    Task::factory()->create(['title' => 'Fix checkout tax']);
    Project::factory()->create(['name' => 'Checkout']);

    Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)
        ->set('query', 'checkout')
        ->assertSee('Checkout 500')
        ->assertSee('Fix checkout tax')
        ->assertSeeHtml('data-ob-command-item');
});

it('finds a task by its identifier', function () {
    $task = Task::factory()->create(['title' => 'Unrelated title']);

    Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)
        ->set('query', $task->identifier)
        ->assertSee('Unrelated title');
});

it('excludes archived tasks', function () {
    Task::factory()->create(['title' => 'Old checkout', 'archived_at' => now()]);

    Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)
        ->set('query', 'checkout')
        ->assertDontSee('Old checkout');
});

it('excludes archived reports', function () {
    Report::factory()->create(['title' => 'Old checkout report', 'archived_at' => now()]);

    Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)
        ->set('query', 'checkout')
        ->assertDontSee('Old checkout report');
});

it('does not query for one character', function () {
    Task::factory()->create(['title' => 'C task']);

    Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)
        ->set('query', 'c')
        ->assertDontSee('C task');
});

it('caps each group at five', function () {
    Task::factory()->count(7)->sequence(fn ($sequence) => ['title' => "Checkout {$sequence->index}"])->create();

    $html = Livewire::actingAs(User::factory()->create())->test(CommandPalette::class)
        ->set('query', 'checkout')
        ->html();

    expect(substr_count($html, '/tasks/'))->toBe(5);
});

it('opens the new task modal from the create query string', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->withQueryParams(['create' => '1'])->test(Index::class)
        ->assertSet('showCreate', true)
        ->assertSet('assigneeId', (string) $user->id);
});

it('leaves the new task modal closed without the create query string', function () {
    Livewire::actingAs(User::factory()->create())->test(Index::class)->assertSet('showCreate', false);
});
