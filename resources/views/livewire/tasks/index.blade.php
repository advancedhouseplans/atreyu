@php
    $toOptions = fn ($items, $value = 'value') => collect($items)->map(fn ($item) => ['value' => (string) $item->{$value}, 'label' => $item->label()])->all();

    $projectOptions = $projects->map(fn ($project) => ['value' => (string) $project->id, 'label' => $project->name])->all();
    $assigneeOptions = $users->map(fn ($user) => ['value' => (string) $user->id, 'label' => $user->name])->all();
    $typeOptions = $toOptions($types);
    $statusOptions = $toOptions($statuses);
    $difficultyOptions = $toOptions($difficulties);
    $priorityOptions = $toOptions($priorities);
    $sortOptions = [
        ['value' => 'updated_at', 'label' => 'Recently updated'],
        ['value' => 'created_at', 'label' => 'Recently created'],
        ['value' => 'priority', 'label' => 'Priority'],
        ['value' => 'due_date', 'label' => 'Due date'],
        ['value' => 'difficulty', 'label' => 'Difficulty'],
        ['value' => 'title', 'label' => 'Title'],
    ];

    $activeFilters = [];
    if ($scope !== 'all') {
        $activeFilters['scope'] = $scope === 'mine' ? 'My work' : 'Unassigned';
    }
    if ($display === 'list' && $status !== '') {
        $activeFilters['status'] = collect($statusOptions)->firstWhere('value', $status)['label'] ?? str($status)->headline();
    }
    if ($project !== '') {
        $activeFilters['project'] = $projects->firstWhere('id', (int) $project)?->name ?? 'Project';
    }
    if ($priorityFilter !== '') {
        $activeFilters['priority'] = collect($priorityOptions)->firstWhere('value', $priorityFilter)['label'] ?? str($priorityFilter)->headline();
    }
    if ($visibility === 'archived') {
        $activeFilters['visibility'] = 'Archived';
    }

    $priorityColor = fn ($priority) => match ($priority) {
        \App\Enums\TaskPriority::Urgent => 'danger',
        \App\Enums\TaskPriority::High => 'warning',
        \App\Enums\TaskPriority::Normal => 'info',
        default => 'neutral',
    };
    $statusColor = fn ($taskStatus) => match ($taskStatus) {
        \App\Enums\TaskStatus::Done => 'success',
        \App\Enums\TaskStatus::InProgress => 'accent',
        \App\Enums\TaskStatus::Review => 'warning',
        default => 'neutral',
    };
    $typeColor = fn ($type) => $type === \App\Enums\TaskType::Bug ? 'danger' : 'info';
@endphp

<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::page-header title="Tasks">
        <x-slot:actions>
            <x-ob::button variant="primary" icon="plus" wire:click="openCreate">New task</x-ob::button>
        </x-slot:actions>
    </x-ob::page-header>

    <div class="mb-5 space-y-3" x-data="{ filtersOpen: false }">
        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
            <div class="min-w-0">
                <x-ob::input icon="magnifying-glass" type="search" wire:model.live.debounce.300ms="q" placeholder="Search tasks or ID" aria-label="Search tasks" />
            </div>
            <div class="flex flex-nowrap items-center gap-2">
                <x-ob::button variant="secondary" icon="funnel" x-on:click="filtersOpen = ! filtersOpen" x-bind:aria-expanded="filtersOpen.toString()" aria-controls="task-filters">
                    Filters
                    @if (count($activeFilters))
                        <span class="grid size-5 place-items-center rounded-full bg-accent text-[11px] text-on-accent">{{ count($activeFilters) }}</span>
                    @endif
                </x-ob::button>

                @if ($display === 'list')
                    <x-ob::dropdown>
                        <x-slot:trigger>
                            <x-ob::button variant="secondary" :icon="$sortDir === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'" icon:trailing="chevron-down">Sort</x-ob::button>
                        </x-slot:trigger>
                        @foreach ($sortOptions as $option)
                            <x-ob::menu.item :icon="$sortBy === $option['value'] ? 'check' : null" wire:click="$set('sortBy', '{{ $option['value'] }}')">{{ $option['label'] }}</x-ob::menu.item>
                        @endforeach
                        <x-ob::menu.separator />
                        <x-ob::menu.item :icon="$sortDir === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'" wire:click="$set('sortDir', '{{ $sortDir === 'asc' ? 'desc' : 'asc' }}')">
                            {{ $sortDir === 'asc' ? 'Ascending' : 'Descending' }}
                        </x-ob::menu.item>
                    </x-ob::dropdown>
                @endif

                <x-ob::segmented
                    name="display"
                    label="Display"
                    wire:model.live="display"
                    class="shrink-0"
                    :options="[['value' => 'board', 'label' => 'Board', 'icon' => 'view-columns'], ['value' => 'list', 'label' => 'List', 'icon' => 'list-bullet']]"
                />
            </div>
        </div>

        <div id="task-filters" x-show="filtersOpen" x-cloak x-transition class="grid gap-3 rounded-xl border border-hairline bg-surface-1 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <x-ob::select label="Assignee" wire:model.live="scope" :options="[['value' => 'mine', 'label' => 'My work'], ['value' => 'all', 'label' => 'Everyone'], ['value' => 'unassigned', 'label' => 'Unassigned']]" aria-label="Filter by assignee" />
            @if ($display === 'list')
                <x-ob::select label="Status" wire:model.live="status" placeholder="Any status" :options="$statusOptions" aria-label="Filter by status" />
            @endif
            <x-ob::select label="Project" wire:model.live="project" placeholder="Any project" :options="$projectOptions" aria-label="Filter by project" />
            <x-ob::select label="Priority" wire:model.live="priorityFilter" placeholder="Any priority" :options="$priorityOptions" aria-label="Filter by priority" />
            <x-ob::select label="Visibility" wire:model.live="visibility" :options="[['value' => 'active', 'label' => 'Active'], ['value' => 'archived', 'label' => 'Archived']]" aria-label="Filter by visibility" />
            @if (count($activeFilters))
                <div class="flex items-end">
                    <x-ob::button variant="ghost" wire:click="clearFilters">Clear all</x-ob::button>
                </div>
            @endif
        </div>

        @if (count($activeFilters))
            <div class="flex flex-wrap gap-2" aria-label="Active filters">
                @foreach ($activeFilters as $filter => $label)
                    <x-ob::button variant="secondary" size="sm" icon:trailing="x-mark" wire:click="clearFilter('{{ $filter }}')">{{ $label }}</x-ob::button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($display === 'board')
        <x-ob::kanban>
            @foreach ($statuses as $laneStatus)
                @php($laneTasks = $boardTasks->get($laneStatus->value, collect()))
                <x-ob::kanban.column :id="$laneStatus->value" :title="$laneStatus->label()" :count="$laneTasks->count()" sort="moveTask" group="tasks" wire:key="lane-{{ $laneStatus->value }}">
                    @foreach ($laneTasks as $task)
                        <x-ob::kanban.card :id="$task->id" :label="'Move '.$task->title" wire:key="board-task-{{ $task->id }}">
                            <x-ob::badge :color="$typeColor($task->type)">{{ $task->type->label() }}</x-ob::badge>
                            <a href="{{ route('tasks.show', $task) }}" wire:navigate class="mt-2 block text-sm font-medium leading-snug hover:text-accent-fg focus-ring rounded">{{ $task->title }}</a>
                            @if ($task->description)
                                <p class="mt-1 line-clamp-2 text-xs text-fg-3">{{ $task->description }}</p>
                            @endif
                            <div class="mt-3 flex items-center justify-between gap-3 border-t border-hairline pt-3 text-xs text-fg-3">
                                <span class="min-w-0 truncate"><span class="font-mono">{{ $task->identifier }}</span> · {{ $task->project->name }}</span>
                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($task->priority !== \App\Enums\TaskPriority::Normal)
                                        <x-ob::badge :color="$priorityColor($task->priority)">{{ $task->priority->label() }}</x-ob::badge>
                                    @endif
                                    @if ($task->due_date)
                                        <span @class(['font-medium', 'text-danger-fg' => $task->due_date->isBefore(today()) && $task->status !== \App\Enums\TaskStatus::Done])>{{ $task->due_date->format('M j') }}</span>
                                    @endif
                                    @if ($task->difficulty)
                                        <span title="Difficulty {{ $task->difficulty->value }} of 5">{{ str_repeat('●', $task->difficulty->value) }}</span>
                                    @endif
                                    @if ($task->assignee)
                                        <x-ob::avatar size="sm" :initials="$task->assignee->initials()" title="{{ $task->assignee->name }}" />
                                    @endif
                                </div>
                            </div>
                        </x-ob::kanban.card>
                    @endforeach
                    <x-slot:empty>Drop a task here</x-slot:empty>
                </x-ob::kanban.column>
            @endforeach
        </x-ob::kanban>
    @elseif ($tasks->isEmpty())
        <x-ob::card :padding="false">
            <x-ob::empty-state icon="clipboard-document-check" heading="Nothing to do?">Create a task or pick one from Reports.</x-ob::empty-state>
        </x-ob::card>
    @else
        <x-ob::table>
            <x-slot:head>
                <x-ob::table.head class="w-[28%]">Task</x-ob::table.head>
                <x-ob::table.head class="w-[12%]">Project</x-ob::table.head>
                <x-ob::table.head class="w-[7%]">Type</x-ob::table.head>
                <x-ob::table.head class="w-[9%]">Priority</x-ob::table.head>
                <x-ob::table.head class="w-[9%]">Difficulty</x-ob::table.head>
                <x-ob::table.head class="w-[11%]">Status</x-ob::table.head>
                <x-ob::table.head class="w-[11%]">Due</x-ob::table.head>
                <x-ob::table.head class="w-[13%]">Assignee</x-ob::table.head>
            </x-slot:head>

            @foreach ($tasks as $task)
                <x-ob::table.row :href="route('tasks.show', $task)" wire:key="task-{{ $task->id }}" wire:transition>
                    <x-ob::table.cell mobile="primary">
                        <span class="block font-mono text-[11px] text-fg-3">{{ $task->identifier }}</span>
                        <span class="font-medium">{{ $task->title }}</span>
                        @if ($task->report)
                            <span class="block truncate text-xs text-fg-3">From report #{{ $task->report->id }}</span>
                        @endif
                    </x-ob::table.cell>
                    <x-ob::table.cell mobile="meta">{{ $task->project->name }}</x-ob::table.cell>
                    <x-ob::table.cell mobile="hide"><x-ob::badge :color="$typeColor($task->type)">{{ $task->type->label() }}</x-ob::badge></x-ob::table.cell>
                    <x-ob::table.cell mobile="meta"><x-ob::badge :color="$priorityColor($task->priority)">{{ $task->priority->label() }}</x-ob::badge></x-ob::table.cell>
                    <x-ob::table.cell mobile="hide">{{ $task->difficulty?->value ? '● '.$task->difficulty->value.'/5' : '—' }}</x-ob::table.cell>
                    <x-ob::table.cell mobile="meta"><x-ob::badge :color="$statusColor($task->status)">{{ $task->status->label() }}</x-ob::badge></x-ob::table.cell>
                    <x-ob::table.cell mobile="meta" class="{{ $task->due_date?->isBefore(today()) && $task->status !== \App\Enums\TaskStatus::Done ? 'text-danger-fg' : '' }}">{{ $task->due_date?->format('M j, Y') ?? '—' }}</x-ob::table.cell>
                    <x-ob::table.cell mobile="hide">{{ $task->assignee?->name ?? 'Unassigned' }}</x-ob::table.cell>
                </x-ob::table.row>
            @endforeach
        </x-ob::table>

        @if ($tasks->hasPages())
            <div class="mt-4">
                {{ $tasks->links() }}
            </div>
        @endif
    @endif

    <x-ob::modal wire:model="showCreate" title="New task" description="Add work directly to a project" size="lg">
        <form id="create-task-form" wire:submit="createTask" class="space-y-4">
            <x-ob::input label="Title" wire:model="title" placeholder="Build a new PC for the new employee" required />
            <x-ob::textarea label="Description" wire:model="description" rows="4" placeholder="Add the context someone needs to pick this up." hint="Markdown supported" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ob::combobox label="Project" wire:model="projectId" placeholder="Choose a project" :options="$projectOptions" />
                <x-ob::combobox label="Assignee" wire:model="assigneeId" placeholder="Unassigned" :options="[['value' => '', 'label' => 'Unassigned'], ...$assigneeOptions]" />
                <x-ob::select label="Type" wire:model="type" :options="$typeOptions" required />
                <x-ob::select label="Difficulty" wire:model="difficulty" placeholder="Not rated" :options="$difficultyOptions" />
                <x-ob::select label="Priority" wire:model="priority" :options="$priorityOptions" required />
                <x-ob::input label="Due date" wire:model="dueDate" type="date" />
            </div>
        </form>

        <x-slot:footer>
            <x-ob::button variant="ghost" wire:click="$set('showCreate', false)">Cancel</x-ob::button>
            <x-ob::button type="submit" form="create-task-form" variant="primary">Create task</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>
</div>
