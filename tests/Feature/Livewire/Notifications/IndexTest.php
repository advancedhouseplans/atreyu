<?php

use App\Livewire\Notifications\Index;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Livewire\Livewire;

it('shows unread notifications and marks one read when opened', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['title' => 'Review the checkout fix']);
    $user->notify(new TaskAssigned($task));
    $notification = $user->notifications()->sole();

    Livewire::actingAs($user)->test(Index::class)
        ->assertSee('Task assigned to you')
        ->assertSee('Review the checkout fix')
        ->call('openNotification', $notification->id)
        ->assertRedirect(route('tasks.show', $task));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks every notification as read', function () {
    $user = User::factory()->create();
    $user->notify(new TaskAssigned(Task::factory()->create()));
    $user->notify(new TaskAssigned(Task::factory()->create()));

    Livewire::actingAs($user)->test(Index::class)
        ->call('markAllRead')
        ->assertSee("You're all caught up");

    expect($user->unreadNotifications()->count())->toBe(0);
});
