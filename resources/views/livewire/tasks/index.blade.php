@php
    $projectOptions = $projects->map(fn ($project) => ['id' => (string) $project->id, 'name' => $project->name]);
    $userOptions = $users->map(fn ($user) => ['id' => (string) $user->id, 'name' => $user->name]);
    $typeOptions = collect($types)->map(fn ($type) => ['id' => $type->value, 'name' => $type->label()]);
    $statusOptions = collect($statuses)->map(fn ($taskStatus) => ['id' => $taskStatus->value, 'name' => $taskStatus->label()]);
    $difficultyOptions = collect($difficulties)->map(fn ($item) => ['id' => (string) $item->value, 'name' => $item->label()]);
    $priorityOptions = collect($priorities)->map(fn ($item) => ['id' => $item->value, 'name' => $item->label()]);
@endphp

<div class="p-5 sm:p-7 lg:p-8" wire:loading.class="opacity-70">
    <x-ob::page-header title="Tasks">
        <x-slot:actions>
            <x-button class="btn-primary rounded-full px-5 shadow-sm" icon="o-plus" wire:click="openCreate" label="New task" />
        </x-slot:actions>
    </x-ob::page-header>

    <div class="mb-5 flex flex-col justify-between gap-3 xl:flex-row xl:items-center">
        <div class="grid flex-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <x-input class="rounded-full bg-base-100/75 shadow-sm backdrop-blur-xl" icon="o-magnifying-glass" type="search" wire:model.live.debounce.300ms="q" placeholder="Search tasks or ID" />
            <x-select class="rounded-full bg-base-100/75 shadow-sm backdrop-blur-xl" icon="o-user" wire:model.live="scope" :options="[['id' => 'mine', 'name' => 'My work'], ['id' => 'all', 'name' => 'Everyone'], ['id' => 'unassigned', 'name' => 'Unassigned']]" />
            @if ($display === 'list')
                <x-select class="rounded-full bg-base-100/75 shadow-sm backdrop-blur-xl" icon="o-bars-3-bottom-left" wire:model.live="status" placeholder="Any status" placeholder-value="" :options="$statusOptions" />
            @endif
            <x-select class="rounded-full bg-base-100/75 shadow-sm backdrop-blur-xl" icon="o-folder" wire:model.live="project" placeholder="Any project" placeholder-value="" :options="$projectOptions" />
            <x-select class="rounded-full bg-base-100/75 shadow-sm backdrop-blur-xl" icon="o-flag" wire:model.live="priorityFilter" placeholder="Any priority" placeholder-value="" :options="$priorityOptions" />
            <x-select class="rounded-full bg-base-100/75 shadow-sm backdrop-blur-xl" icon="o-archive-box" wire:model.live="visibility" :options="[['id' => 'active', 'name' => 'Active'], ['id' => 'archived', 'name' => 'Archived']]" />
            @if ($display === 'list')
                <x-select class="rounded-full bg-base-100/75 shadow-sm backdrop-blur-xl" icon="o-arrows-up-down" wire:model.live="sortBy" :options="[
                    ['id' => 'updated_at', 'name' => 'Recently updated'],
                    ['id' => 'created_at', 'name' => 'Recently created'],
                    ['id' => 'priority', 'name' => 'Priority'],
                    ['id' => 'due_date', 'name' => 'Due date'],
                    ['id' => 'difficulty', 'name' => 'Difficulty'],
                    ['id' => 'title', 'name' => 'Title'],
                ]" />
                <button type="button" class="btn btn-square btn-secondary" wire:click="$set('sortDir', '{{ $sortDir === 'asc' ? 'desc' : 'asc' }}')" aria-label="Sort {{ $sortDir === 'asc' ? 'descending' : 'ascending' }}" title="Sort {{ $sortDir === 'asc' ? 'descending' : 'ascending' }}">
                    <x-icon :name="$sortDir === 'asc' ? 'o-bars-arrow-up' : 'o-bars-arrow-down'" class="size-5" />
                </button>
            @endif
        </div>
        <div class="join self-start rounded-full bg-base-100/70 p-1 shadow-sm ring-1 ring-base-content/5 backdrop-blur-xl">
            <button type="button" wire:click="$set('display', 'board')" @class(['btn btn-sm join-item', 'btn-primary' => $display === 'board', 'btn-ghost' => $display !== 'board']) aria-label="Board view"><x-icon name="o-view-columns" class="size-4" />Board</button>
            <button type="button" wire:click="$set('display', 'list')" @class(['btn btn-sm join-item', 'btn-primary' => $display === 'list', 'btn-ghost' => $display !== 'list']) aria-label="List view"><x-icon name="o-list-bullet" class="size-4" />List</button>
        </div>
    </div>

    @if ($display === 'board')
        <div class="overflow-x-auto pb-3">
            <div class="grid min-w-[70rem] grid-cols-4 gap-4">
                @foreach ($statuses as $laneStatus)
                    @php
                        $laneTasks = $boardTasks->get($laneStatus->value, collect());
                        $laneAccent = match ($laneStatus) {
                            \App\Enums\TaskStatus::InProgress => 'bg-primary',
                            \App\Enums\TaskStatus::Review => 'bg-warning',
                            \App\Enums\TaskStatus::Done => 'bg-success',
                            default => 'bg-base-content/25',
                        };
                    @endphp
                    <section class="rounded-3xl border border-base-content/5 bg-base-100/45 p-3 shadow-[0_16px_45px_-36px_rgba(0,0,0,0.55)] backdrop-blur-xl" wire:key="lane-{{ $laneStatus->value }}">
                        <header class="mb-3 flex items-center justify-between gap-3 px-1">
                            <div class="flex items-center gap-2"><span class="size-2 rounded-full {{ $laneAccent }}"></span><h2 class="font-semibold">{{ $laneStatus->label() }}</h2></div>
                            <span class="badge badge-sm badge-ghost">{{ $laneTasks->count() }}</span>
                        </header>

                        <div
                            class="flex min-h-52 flex-col gap-3 rounded-box"
                            wire:sort="moveTask"
                            wire:sort:group="tasks"
                            wire:sort:group-id="{{ $laneStatus->value }}"
                        >
                            @foreach ($laneTasks as $task)
                                <article class="card cursor-default rounded-2xl border border-base-content/5 bg-base-100/85 shadow-[0_10px_30px_-24px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5" wire:key="board-task-{{ $task->id }}" wire:sort:item="{{ $task->id }}">
                                    <div class="card-body gap-3 p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <span @class(['badge badge-sm', 'badge-error' => $task->type === \App\Enums\TaskType::Bug, 'badge-info' => $task->type === \App\Enums\TaskType::Build])>{{ $task->type->label() }}</span>
                                            <button type="button" class="btn btn-ghost btn-xs btn-square cursor-grab touch-none text-base-content/45 active:cursor-grabbing" wire:sort:handle aria-label="Move {{ $task->title }}"><x-icon name="o-bars-3" class="size-4" /></button>
                                        </div>

                                        <div wire:sort:ignore>
                                            <a href="{{ route('tasks.show', $task) }}" wire:navigate class="font-semibold leading-snug hover:text-primary">{{ $task->title }}</a>
                                            @if ($task->description)<p class="mt-2 line-clamp-2 text-xs leading-5 text-base-content/50">{{ $task->description }}</p>@endif
                                        </div>

                                        <div class="flex items-center justify-between gap-3 border-t border-base-content/10 pt-3 text-xs text-base-content/50">
                                            <span class="min-w-0 truncate"><span class="font-mono text-[0.65rem] font-semibold text-base-content/35">{{ $task->identifier }}</span> · {{ $task->project->name }}</span>
                                            <div class="flex shrink-0 items-center gap-2">
                                                @if ($task->priority !== \App\Enums\TaskPriority::Normal)<span @class(['badge badge-xs', 'badge-error' => $task->priority === \App\Enums\TaskPriority::Urgent, 'badge-warning' => $task->priority === \App\Enums\TaskPriority::High, 'badge-ghost' => $task->priority === \App\Enums\TaskPriority::Low])>{{ $task->priority->label() }}</span>@endif
                                                @if ($task->due_date)<span @class(['font-medium', 'text-error' => $task->due_date->isBefore(today()) && $task->status !== \App\Enums\TaskStatus::Done])>{{ $task->due_date->format('M j') }}</span>@endif
                                                @if ($task->difficulty)<span class="font-medium" title="Difficulty {{ $task->difficulty->value }} of 5">{{ str_repeat('●', $task->difficulty->value) }}</span>@endif
                                                @if ($task->assignee)<div class="avatar avatar-placeholder" title="{{ $task->assignee->name }}"><div class="w-7 rounded-full bg-neutral text-neutral-content"><span class="text-[0.65rem]">{{ $task->assignee->initials() }}</span></div></div>@endif
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach

                            @if ($laneTasks->isEmpty())
                                <div class="grid min-h-28 place-items-center rounded-box border border-dashed border-base-content/15 px-4 text-center text-xs text-base-content/40">Drop a task here</div>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    @else
        <section class="overflow-hidden rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_20px_60px_-38px_rgba(0,0,0,0.55)] ring-1 ring-base-content/5 backdrop-blur-xl">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Task</th><th>Project</th><th>Type</th><th>Priority</th><th>Difficulty</th><th>Status</th><th>Due</th><th>Assignee</th></tr></thead>
                    <tbody>
                        @forelse ($tasks as $task)
                            @php
                                $statusClass = match ($task->status) {
                                    \App\Enums\TaskStatus::Done => 'badge-success',
                                    \App\Enums\TaskStatus::InProgress => 'badge-primary',
                                    \App\Enums\TaskStatus::Review => 'badge-warning',
                                    default => 'badge-ghost',
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-base-content/[0.035]" wire:key="task-{{ $task->id }}">
                                <td class="min-w-72"><div class="mb-1 font-mono text-[0.65rem] font-semibold text-base-content/35">{{ $task->identifier }}</div><a href="{{ route('tasks.show', $task) }}" wire:navigate class="font-semibold hover:text-primary">{{ $task->title }}</a>@if ($task->report)<div class="mt-1 text-xs text-base-content/45">From report #{{ $task->report->id }}</div>@endif</td>
                                <td><span class="badge badge-secondary">{{ $task->project->name }}</span></td>
                                <td><span @class(['badge badge-sm', 'badge-error' => $task->type === \App\Enums\TaskType::Bug, 'badge-info' => $task->type === \App\Enums\TaskType::Build])>{{ $task->type->label() }}</span></td>
                                <td><span @class(['badge badge-sm', 'badge-error' => $task->priority === \App\Enums\TaskPriority::Urgent, 'badge-warning' => $task->priority === \App\Enums\TaskPriority::High, 'badge-info' => $task->priority === \App\Enums\TaskPriority::Normal, 'badge-ghost' => $task->priority === \App\Enums\TaskPriority::Low])>{{ $task->priority->label() }}</span></td>
                                <td>{{ $task->difficulty?->value ? '● '.$task->difficulty->value.'/5' : '—' }}</td>
                                <td><span class="badge badge-sm {{ $statusClass }}">{{ $task->status->label() }}</span></td>
                                <td @class(['text-sm', 'font-semibold text-error' => $task->due_date?->isBefore(today()) && $task->status !== \App\Enums\TaskStatus::Done])>{{ $task->due_date?->format('M j, Y') ?? '—' }}</td>
                                <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-16 text-center"><div class="font-semibold">Nothing to do?</div><div class="mt-1 text-sm text-base-content/50">Create a task or pick one from Reports.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tasks->hasPages())<div class="border-t border-base-content/10 p-4">{{ $tasks->links() }}</div>@endif
        </section>
    @endif

    <x-modal wire:model="showCreate" title="New task" subtitle="Add work directly to a project" box-class="max-w-2xl">
        <form wire:submit="createTask" class="space-y-4">
            <x-input label="Title" wire:model="title" placeholder="Build a new PC for the new employee" required autofocus />
            <x-textarea label="Description" wire:model="description" rows="4" placeholder="Add the context someone needs to pick this up." />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select label="Project" wire:model="projectId" placeholder="Choose a project" :options="$projectOptions" required />
                <x-select label="Assignee" wire:model="assigneeId" placeholder="Unassigned" placeholder-value="" :options="$userOptions" />
                <x-select label="Type" wire:model="type" :options="$typeOptions" required />
                <x-select label="Difficulty" wire:model="difficulty" placeholder="Not rated" placeholder-value="" :options="$difficultyOptions" />
                <x-select label="Priority" wire:model="priority" :options="$priorityOptions" required />
                <x-input label="Due date" wire:model="dueDate" type="date" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" class="btn-ghost" wire:click="$set('showCreate', false)" label="Cancel" />
                <x-button type="submit" class="btn-primary" spinner="createTask" label="Create task" />
            </div>
        </form>
    </x-modal>
</div>
