<div class="p-5 sm:p-7 lg:p-8">
    <a href="{{ route('tasks.index') }}" wire:navigate class="btn btn-ghost btn-sm mb-5 -ml-2"><x-icon name="o-arrow-left" class="size-4" />Tasks</a>

    <div class="mb-8 flex flex-col justify-between gap-5 lg:flex-row lg:items-start">
        <div class="min-w-0">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span class="font-mono text-xs font-bold text-base-content/45">{{ $task->identifier }}</span>
                <span class="badge badge-secondary">{{ $task->project->name }}</span>
                <span @class(['badge', 'badge-error' => $task->type === \App\Enums\TaskType::Bug, 'badge-info' => $task->type === \App\Enums\TaskType::Build])>{{ $task->type->label() }}</span>
                @if ($task->report)<a href="{{ route('reports.show', $task->report) }}" wire:navigate class="badge badge-ghost">Report #{{ $task->report->id }}</a>@endif
            </div>
            <h1 class="max-w-4xl text-2xl font-semibold tracking-[-0.025em] sm:text-[1.7rem]">{{ $task->title }}</h1>
            <p class="mt-3 text-sm text-base-content/50">Created {{ $task->created_at->diffForHumans() }} by {{ $task->creator?->name ?? 'Unknown' }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($task->archived_at)
                <span class="badge badge-ghost h-8">Archived</span>
                <x-button class="btn-secondary btn-sm" icon="o-arrow-path" wire:click="reopenTask" label="Reopen" />
            @else
                <x-button class="btn-ghost btn-sm" icon="o-pencil-square" wire:click="$set('showEdit', true)" label="Edit" />
            @endif
            <details class="dropdown dropdown-end">
                <summary class="btn btn-secondary btn-sm">{{ $task->status->label() }}</summary>
                <ul class="menu dropdown-content z-20 mt-2 w-48 rounded-2xl border border-base-content/5 bg-base-100/80 p-2 shadow-2xl ring-1 ring-base-content/5 backdrop-blur-2xl">
                    @foreach ($statuses as $status)<li><button type="button" wire:click="updateStatus('{{ $status->value }}')">{{ $status->label() }}</button></li>@endforeach
                </ul>
            </details>
            @unless ($task->archived_at)
                <x-button class="btn-ghost btn-sm text-error" icon="o-archive-box" wire:click="archiveTask" wire:confirm="Archive this task? It can be reopened later." label="Archive" />
            @endunless
            <details class="dropdown dropdown-end">
                <summary class="btn btn-primary btn-sm">{{ $task->assignee?->name ?? 'Assign task' }}</summary>
                <ul class="menu dropdown-content z-20 mt-2 max-h-72 w-56 overflow-y-auto rounded-2xl border border-base-content/5 bg-base-100/80 p-2 shadow-2xl ring-1 ring-base-content/5 backdrop-blur-2xl">
                    <li><button type="button" wire:click="updateAssignee(null)">Unassigned</button></li>
                    @foreach ($users as $user)<li><button type="button" wire:click="updateAssignee({{ $user->id }})">{{ $user->name }}</button></li>@endforeach
                </ul>
            </details>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-6">
            <x-card title="Description" class="rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
                <div class="whitespace-pre-wrap text-sm leading-6 text-base-content/75">{{ $task->description ?: 'No description was provided.' }}</div>
            </x-card>

            <x-card title="Activity" class="rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
                <div class="space-y-3">
                    @forelse ($timeline as $entry)
                        @if ($entry['kind'] === 'comment')
                            <div wire:key="comment-{{ $entry['item']->id }}" class="rounded-box bg-base-200 p-4">
                                <div class="mb-2 flex justify-between gap-3 text-xs"><span class="font-semibold">{{ $entry['item']->author->name }}</span><span class="text-base-content/45">{{ $entry['item']->created_at->diffForHumans() }}</span></div>
                                <div class="whitespace-pre-wrap text-sm text-base-content/80">{{ $entry['item']->body }}</div>
                            </div>
                        @else
                            <div wire:key="activity-{{ $entry['item']->id }}" class="flex items-start gap-3 px-1 py-2 text-sm">
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary/50"></span>
                                <div class="min-w-0 flex-1"><span class="font-semibold">{{ $entry['item']->actor?->name ?? 'Atreyu' }}</span> {{ $entry['item']->summary() }}<div class="mt-1 text-xs text-base-content/40">{{ $entry['item']->created_at->diffForHumans() }}</div></div>
                            </div>
                        @endif
                    @empty
                        <p class="text-sm text-base-content/50">No activity yet.</p>
                    @endforelse
                </div>
                <form wire:submit="addComment" class="mt-5 space-y-3">
                    <x-textarea wire:model="newComment" rows="4" placeholder="Add context, ask a question, or leave an update…" />
                    <div class="flex justify-end"><x-button type="submit" class="btn-primary btn-sm" spinner="addComment" label="Post comment" /></div>
                </form>
            </x-card>
        </div>

        <aside>
            <x-card title="Task details" class="rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
                <dl class="space-y-5 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Project</dt><dd class="mt-1">{{ $task->project->name }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Priority</dt><dd class="mt-1"><span @class(['badge badge-sm', 'badge-error' => $task->priority === \App\Enums\TaskPriority::Urgent, 'badge-warning' => $task->priority === \App\Enums\TaskPriority::High, 'badge-info' => $task->priority === \App\Enums\TaskPriority::Normal, 'badge-ghost' => $task->priority === \App\Enums\TaskPriority::Low])>{{ $task->priority->label() }}</span></dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Due date</dt><dd @class(['mt-1', 'font-semibold text-error' => $task->due_date?->isBefore(today()) && $task->status !== \App\Enums\TaskStatus::Done])>{{ $task->due_date?->format('M j, Y') ?? 'No due date' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Difficulty</dt><dd class="mt-2 flex flex-wrap gap-1">
                        @foreach ($difficulties as $difficulty)
                            <button type="button" wire:click="updateDifficulty({{ $difficulty->value }})" @class(['btn btn-xs', 'btn-primary' => $task->difficulty === $difficulty, 'btn-ghost' => $task->difficulty !== $difficulty])>{{ $difficulty->value }}</button>
                        @endforeach
                    </dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Assignee</dt><dd class="mt-1">{{ $task->assignee?->name ?? 'Unassigned' }}</dd></div>
                </dl>
            </x-card>
        </aside>
    </div>

    <x-modal wire:model="showEdit" title="Edit {{ $task->identifier }}" subtitle="Update the task details" box-class="max-w-2xl">
        <form wire:submit="editTask" class="space-y-4">
            <x-input label="Title" wire:model="editTitle" required autofocus />
            <x-textarea label="Description" wire:model="editDescription" rows="6" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select label="Project" wire:model="editProjectId" :options="$projects->map(fn ($project) => ['id' => (string) $project->id, 'name' => $project->name])" required />
                <x-select label="Type" wire:model="editType" :options="collect($types)->map(fn ($type) => ['id' => $type->value, 'name' => $type->label()])" required />
                <x-select label="Priority" wire:model="editPriority" :options="collect($priorities)->map(fn ($priority) => ['id' => $priority->value, 'name' => $priority->label()])" required />
                <x-input label="Due date" wire:model="editDueDate" type="date" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" class="btn-ghost" wire:click="$set('showEdit', false)" label="Cancel" />
                <x-button type="submit" class="btn-primary" spinner="editTask" label="Save changes" />
            </div>
        </form>
    </x-modal>
</div>
