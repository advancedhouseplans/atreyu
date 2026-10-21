@php
    $toOptions = fn ($items) => collect($items)->map(fn ($item) => ['value' => (string) $item->value, 'label' => $item->label()])->all();

    $assigneeOptions = $users->map(fn ($user) => ['value' => (string) $user->id, 'label' => $user->name])->all();
    $projectOptions = $projects->map(fn ($project) => ['value' => (string) $project->id, 'label' => $project->name])->all();
    $typeOptions = $toOptions($types);
    $statusOptions = $toOptions($statuses);
    $priorityOptions = $toOptions($priorities);
    $difficultyOptions = $toOptions($difficulties);

    $typeColor = $task->type === \App\Enums\TaskType::Bug ? 'danger' : 'info';
    $isOverdue = $task->due_date?->isBefore(today()) && $task->status !== \App\Enums\TaskStatus::Done;
    $completedSubtasks = $task->subtasks->where('status', \App\Enums\TaskStatus::Done)->count();
@endphp

<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::button variant="ghost" size="sm" icon="chevron-left" :href="route('tasks.index')" navigate class="-ml-3 mb-5">Tasks</x-ob::button>

    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span class="font-mono text-xs font-medium text-fg-3">{{ $task->identifier }}</span>
                <x-ob::badge>{{ $task->project->name }}</x-ob::badge>
                <x-ob::badge :color="$typeColor">{{ $task->type->label() }}</x-ob::badge>
                @if ($task->archived_at)
                    <x-ob::badge>Archived</x-ob::badge>
                @endif
                @if ($task->report)
                    <a href="{{ route('reports.show', $task->report) }}" wire:navigate class="focus-ring rounded-full"><x-ob::badge color="accent">Report #{{ $task->report->id }}</x-ob::badge></a>
                @endif
                @if ($task->parent)
                    <a href="{{ route('tasks.show', $task->parent) }}" wire:navigate class="focus-ring rounded-full"><x-ob::badge color="accent">Subtask of {{ $task->parent->identifier }}</x-ob::badge></a>
                @endif
            </div>
            <h1 class="max-w-4xl">
                <x-ob::inline-edit
                    :value="$task->title"
                    action="updateTitle"
                    name="title"
                    label="title"
                    class="[&_button]:text-2xl [&_button]:font-semibold [&_button]:tracking-[-0.02em] [&_button>span]:whitespace-normal"
                />
            </h1>
            <p class="mt-3 text-sm text-fg-2">Created {{ $task->created_at->diffForHumans() }} by {{ $task->creator?->name ?? 'Unknown' }}</p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($task->archived_at)
                <x-ob::button icon="arrow-path" wire:click="reopenTask">Reopen</x-ob::button>
            @else
                <x-ob::button icon="pencil-square" wire:click="$set('showEdit', true)">Edit</x-ob::button>
                <x-ob::dropdown>
                    <x-slot:trigger>
                        <x-ob::button icon="ellipsis-horizontal" label="Actions" />
                    </x-slot:trigger>

                    <x-ob::menu.item icon="archive-box" variant="danger" wire:click="archiveTask" wire:confirm="Archive this task? It can be reopened later.">Archive</x-ob::menu.item>
                </x-ob::dropdown>
            @endif
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <x-ob::card>
                <h2 class="mb-3 text-base font-semibold">Description</h2>
                @if ($task->description)
                    <div class="space-y-3 text-sm leading-6 text-fg-2 [&_a]:text-accent-fg [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:border-hairline-strong [&_blockquote]:pl-4 [&_code]:rounded [&_code]:bg-surface-3 [&_code]:px-1.5 [&_code]:py-0.5 [&_h1]:text-xl [&_h1]:font-semibold [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:font-semibold [&_li]:my-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-surface-3 [&_pre]:p-4 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_ul]:list-disc [&_ul]:pl-5">{!! \Illuminate\Support\Str::markdown($task->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                @else
                    <p class="text-sm text-fg-3">No description was provided.</p>
                @endif
            </x-ob::card>

            @if (! $task->parent_id)
                <x-ob::card>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold">Subtasks</h2>
                        @if ($task->subtasks->isNotEmpty())
                            <x-ob::badge>{{ $completedSubtasks }}/{{ $task->subtasks->count() }} complete</x-ob::badge>
                        @endif
                    </div>

                    <div class="divide-y divide-hairline">
                        @forelse ($task->subtasks as $subtask)
                            <div class="flex min-h-12 items-center gap-3 py-2" wire:key="subtask-{{ $subtask->id }}">
                                <button type="button" wire:click="toggleSubtask({{ $subtask->id }})" aria-label="{{ $subtask->status === \App\Enums\TaskStatus::Done ? 'Reopen' : 'Complete' }} {{ $subtask->title }}" class="grid size-7 shrink-0 place-items-center rounded-full text-fg-3 transition duration-120 ease-ob hover:bg-surface-3 hover:text-accent-fg focus-ring">
                                    @if ($subtask->status === \App\Enums\TaskStatus::Done)
                                        <x-ob::icon name="check-circle" class="size-5" />
                                    @else
                                        <span class="size-4 rounded-full border border-current"></span>
                                    @endif
                                </button>
                                <a href="{{ route('tasks.show', $subtask) }}" wire:navigate class="min-w-0 flex-1 rounded focus-ring">
                                    <span @class(['block truncate text-sm font-medium', 'text-fg-3 line-through' => $subtask->status === \App\Enums\TaskStatus::Done])>{{ $subtask->title }}</span>
                                    <span class="block font-mono text-[11px] text-fg-3">{{ $subtask->identifier }} · {{ $subtask->status->label() }}</span>
                                </a>
                                @if ($subtask->assignee)
                                    <x-ob::avatar size="sm" :initials="$subtask->assignee->initials()" title="{{ $subtask->assignee->name }}" />
                                @endif
                            </div>
                        @empty
                            <p class="py-4 text-sm text-fg-3">Break this task into smaller pieces when it needs them.</p>
                        @endforelse
                    </div>

                    @unless ($task->archived_at)
                        <form wire:submit="addSubtask" class="mt-4 grid grid-cols-[minmax(0,1fr)_auto] items-start gap-2">
                            <x-ob::input wire:model="newSubtaskTitle" placeholder="Add a subtask" aria-label="Subtask title" />
                            <x-ob::button type="submit" variant="secondary" icon="plus">Add</x-ob::button>
                        </form>
                    @endunless
                </x-ob::card>
            @endif

            <x-ob::card>
                <h2 class="mb-3 text-base font-semibold">Activity</h2>
                <div class="space-y-3">
                    @forelse ($timeline as $entry)
                        @if ($entry['kind'] === 'comment')
                            <div wire:key="comment-{{ $entry['item']->id }}" class="rounded-lg bg-surface-3 p-4">
                                <div class="mb-2 flex items-center justify-between gap-3 text-xs text-fg-3">
                                    <span class="font-semibold text-fg-2">{{ $entry['item']->author->name }}</span>
                                    <span>{{ $entry['item']->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="space-y-2 text-sm text-fg-2 [&_a]:text-accent-fg [&_a]:underline [&_code]:rounded [&_code]:bg-surface-2 [&_code]:px-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_ul]:list-disc [&_ul]:pl-5">{!! \Illuminate\Support\Str::markdown($entry['item']->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                            </div>
                        @else
                            <div wire:key="activity-{{ $entry['item']->id }}" class="flex items-start gap-3 px-1 py-2 text-sm">
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-accent"></span>
                                <div class="min-w-0 flex-1">
                                    <span class="font-semibold">{{ $entry['item']->actor?->name ?? 'Atreyu' }}</span> {{ $entry['item']->summary() }}
                                    <div class="mt-1 text-xs text-fg-3">{{ $entry['item']->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <p class="text-sm text-fg-3">No activity yet.</p>
                    @endforelse
                </div>

                <form wire:submit="addComment" class="mt-4 space-y-3">
                    <x-ob::textarea wire:model="newComment" rows="4" placeholder="Add context, ask a question, or leave an update" hint="Markdown supported" aria-label="Comment" />
                    <div class="flex justify-end">
                        <x-ob::button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="addComment">Post comment</x-ob::button>
                    </div>
                </form>
            </x-ob::card>
        </div>

        <aside>
            <x-ob::card class="space-y-1">
                <h2 class="mb-2 text-base font-semibold">Details</h2>
                <x-ob::property label="Status" icon="flag">
                    <x-ob::inline-edit :value="$task->status->value" action="updateStatus" name="status" type="select" :options="$statusOptions" label="status" />
                </x-ob::property>
                <x-ob::property label="Priority" icon="exclamation-triangle">
                    <x-ob::inline-edit :value="$task->priority->value" action="updatePriority" name="priority" type="select" :options="$priorityOptions" label="priority" />
                </x-ob::property>
                <x-ob::property label="Assignee" icon="user">
                    <x-ob::inline-edit :value="$task->assignee_id ? (string) $task->assignee_id : null" action="updateAssignee" name="assignee" type="select" :options="$assigneeOptions" label="assignee" placeholder="Unassigned" />
                </x-ob::property>
                <x-ob::property label="Difficulty" icon="bolt">
                    <x-ob::inline-edit :value="$task->difficulty ? (string) $task->difficulty->value : null" action="updateDifficulty" name="difficulty" type="select" :options="$difficultyOptions" label="difficulty" placeholder="Not rated" />
                </x-ob::property>
                <x-ob::property label="Project" icon="folder">{{ $task->project->name }}</x-ob::property>
                <x-ob::property label="Due date" icon="calendar">
                    <span @class(['font-medium text-danger-fg' => $isOverdue])>{{ $task->due_date?->format('M j, Y') ?? 'No due date' }}</span>
                </x-ob::property>
            </x-ob::card>
        </aside>
    </div>

    <x-ob::modal wire:model="showEdit" :title="'Edit '.$task->identifier" description="Update the task details" size="lg">
        <form id="edit-task-form" wire:submit="editTask" class="space-y-4">
            <x-ob::input label="Title" wire:model="editTitle" required />
            <x-ob::textarea label="Description" wire:model="editDescription" rows="6" hint="Markdown supported" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ob::select label="Project" wire:model="editProjectId" :options="$projectOptions" required />
                <x-ob::select label="Type" wire:model="editType" :options="$typeOptions" required />
                <x-ob::select label="Priority" wire:model="editPriority" :options="$priorityOptions" required />
                <x-ob::input label="Due date" wire:model="editDueDate" type="date" />
            </div>
        </form>

        <x-slot:footer>
            <x-ob::button variant="ghost" wire:click="$set('showEdit', false)">Cancel</x-ob::button>
            <x-ob::button type="submit" form="edit-task-form" variant="primary">Save changes</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>
</div>
