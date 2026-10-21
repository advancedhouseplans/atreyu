@php
    $severityColor = match ($report->severity) {
        'blocker' => 'danger',
        'high' => 'warning',
        'med' => 'info',
        default => 'neutral',
    };
    $matchingProject = $projects->firstWhere('slug', $report->source_slug);
    $statusOptions = collect(['new', 'in_progress', 'done', 'wontfix'])
        ->map(fn ($value) => ['value' => $value, 'label' => str($value)->replace('_', ' ')->ucfirst()->toString()])->all();
    $assigneeOptions = $users->map(fn ($user) => ['value' => (string) $user->id, 'label' => $user->name])->all();

    $projectOptions = $projects->map(fn ($project) => ['value' => (string) $project->id, 'label' => $project->name])->all();
    if (! $matchingProject) {
        array_unshift($projectOptions, ['value' => 'source', 'label' => 'Create '.str($report->source_slug)->headline().' project']);
    }
    $typeOptions = collect($taskTypes)->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()])->all();
    $difficultyOptions = collect($taskDifficulties)->map(fn ($difficulty) => ['value' => (string) $difficulty->value, 'label' => $difficulty->label()])->all();
@endphp

<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::button variant="ghost" size="sm" icon="chevron-left" :href="route('reports.index')" navigate class="-ml-3 mb-5">Reports</x-ob::button>

    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <x-ob::badge>{{ str($report->source_slug)->headline() }}</x-ob::badge>
                <x-ob::badge :color="$report->type === 'bug' ? 'danger' : 'info'">{{ str($report->type)->headline() }}</x-ob::badge>
                <x-ob::badge :color="$severityColor">{{ str($report->severity)->headline() }}</x-ob::badge>
            </div>
            <h1 class="max-w-4xl">
                <x-ob::inline-edit
                    :value="$report->title"
                    action="updateTitle"
                    name="title"
                    label="title"
                    class="[&_button]:text-2xl [&_button]:font-semibold [&_button]:tracking-[-0.02em] [&_button>span]:whitespace-normal"
                />
            </h1>
            <p class="mt-3 text-sm text-fg-2">Reported {{ $report->created_at->diffForHumans() }} by {{ $report->reporter_name ?: ($report->reporter_email ?: 'Anonymous reporter') }}</p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($report->task)
                <x-ob::button variant="primary" icon="check-circle" :href="route('tasks.show', $report->task)" navigate>Open task</x-ob::button>
            @endif

            <x-ob::dropdown>
                <x-slot:trigger>
                    <x-ob::button icon="ellipsis-horizontal" label="Actions" />
                </x-slot:trigger>

                @unless ($report->task)
                    @if ($report->status === 'wontfix')
                        <x-ob::menu.item icon="arrow-path" wire:click="reopenReport">Reopen</x-ob::menu.item>
                    @else
                        <x-ob::menu.item icon="inbox-arrow-down" wire:click="openTriage">Convert to task</x-ob::menu.item>
                        <x-ob::menu.item icon="archive-box" wire:click="dismissReport" wire:confirm="Dismiss this report without creating a task?">Dismiss</x-ob::menu.item>
                    @endif
                @endunless
                <x-ob::menu.item icon="link" x-on:click="navigator.clipboard.writeText(location.href); $dispatch('toast', {message: 'Link copied', variant: 'success'})">Copy link</x-ob::menu.item>
            </x-ob::dropdown>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <x-ob::card>
                <h2 class="mb-3 text-base font-semibold">Description</h2>
                <div class="whitespace-pre-wrap text-sm leading-6 text-fg-2">{{ $report->description ?: 'No description was provided.' }}</div>
            </x-ob::card>

            <x-ob::card>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">Diagnostic timeline</h2>
                    <x-ob::badge>{{ count($timeline) }} {{ str('event')->plural(count($timeline)) }}</x-ob::badge>
                </div>
                @forelse ($timeline as $row)
                    <details class="group border-t border-hairline first:border-t-0" wire:key="timeline-{{ $loop->index }}">
                        <summary class="flex min-h-10 cursor-pointer list-none items-center gap-3 text-sm focus-ring max-sm:min-h-11">
                            <x-ob::badge>{{ $row['kind'] }}</x-ob::badge>
                            <span class="min-w-0 flex-1 truncate">{{ $row['message'] ?? $row['url'] ?? $row['route_name'] ?? 'Captured context' }}</span>
                            <x-ob::icon name="chevron-down" variant="mini" class="size-4 shrink-0 text-fg-3 transition duration-120 ease-ob group-open:rotate-180" />
                        </summary>
                        <pre class="mb-3 overflow-x-auto rounded-lg bg-surface-3 p-4 text-xs"><code>{{ json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                    </details>
                @empty
                    <p class="rounded-lg bg-surface-3 p-6 text-center text-sm text-fg-3">No diagnostic events were captured.</p>
                @endforelse
            </x-ob::card>

            <x-ob::card>
                <h2 class="mb-3 text-base font-semibold">Notes</h2>
                <div class="space-y-3">
                    @forelse ($report->notes as $note)
                        <div class="rounded-lg bg-surface-3 p-4" wire:key="note-{{ $note->id }}">
                            <div class="mb-2 flex items-center justify-between gap-3 text-xs text-fg-3">
                                <span class="font-semibold text-fg-2">{{ $note->author->name }}</span>
                                <span>{{ $note->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="space-y-2 text-sm text-fg-2 [&_a]:text-accent-fg [&_a]:underline [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5">{!! \Illuminate\Support\Str::markdown($note->body) !!}</div>
                        </div>
                    @empty
                        <p class="text-sm text-fg-3">No notes yet.</p>
                    @endforelse
                </div>

                <form wire:submit="addNote" class="mt-4 space-y-3">
                    <x-ob::textarea wire:model="newNote" rows="4" placeholder="Add a note for the team" aria-label="Note" />
                    <div class="flex justify-end">
                        <x-ob::button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="addNote">Post note</x-ob::button>
                    </div>
                </form>
            </x-ob::card>
        </div>

        <aside class="space-y-6">
            <x-ob::card class="space-y-1">
                <h2 class="mb-2 text-base font-semibold">Details</h2>
                <x-ob::property label="Status" icon="flag">
                    <x-ob::inline-edit :value="$report->status" action="updateStatus" name="status" type="select" :options="$statusOptions" label="status" />
                </x-ob::property>
                <x-ob::property label="Assignee" icon="user">
                    <x-ob::inline-edit :value="$report->assignee_id ? (string) $report->assignee_id : null" action="updateAssignee" name="assignee" type="select" :options="$assigneeOptions" label="assignee" placeholder="Unassigned" />
                </x-ob::property>
                <x-ob::property label="Project" icon="folder">{{ $matchingProject?->name ?? str($report->source_slug)->headline() }}</x-ob::property>
                <x-ob::property label="Reporter" icon="user-circle">
                    {{ $report->reporter_name ?: 'Anonymous reporter' }}
                    @if ($report->reporter_email)
                        <span class="block text-fg-3">{{ $report->reporter_email }}</span>
                    @endif
                </x-ob::property>
            </x-ob::card>

            <x-ob::card class="space-y-1">
                <h2 class="mb-2 text-base font-semibold">Report context</h2>
                <x-ob::property label="Environment" class="capitalize">{{ $report->environment }}</x-ob::property>
                <x-ob::property label="Page"><a class="text-accent-fg underline focus-ring" href="{{ $report->url }}" target="_blank" rel="noreferrer">{{ $report->url }}</a></x-ob::property>
                <x-ob::property label="Browser">{{ trim("{$report->browser} {$report->browser_version}") ?: 'Unknown' }}</x-ob::property>
                <x-ob::property label="Operating system">{{ trim("{$report->os} {$report->os_version}") ?: 'Unknown' }}</x-ob::property>
                <x-ob::property label="Viewport">{{ $report->viewport_wh ?: 'Unknown' }} @ {{ $report->dpr ?: 1 }}x</x-ob::property>
            </x-ob::card>

            <x-ob::card>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">Attachments</h2>
                    <x-ob::badge>{{ $report->attachments->count() }}</x-ob::badge>
                </div>
                <ul class="space-y-2 text-sm">
                    @forelse ($report->attachments as $attachment)
                        <li wire:key="attachment-{{ $attachment->id }}">
                            <a class="flex items-center gap-3 rounded-lg bg-surface-3 p-3 transition-colors duration-120 ease-ob hover:bg-surface-2 focus-ring" href="{{ route('reports.attachments.download', [$report, $attachment]) }}">
                                <x-ob::icon name="paper-clip" variant="mini" class="size-5 shrink-0 text-accent-fg" />
                                <span class="min-w-0"><span class="block truncate font-medium">{{ str($attachment->kind)->replace('_', ' ')->title() }}</span><span class="text-xs text-fg-3">{{ $attachment->mime }} · {{ \Illuminate\Support\Number::fileSize($attachment->size) }}</span></span>
                            </a>
                        </li>
                    @empty
                        <li class="text-fg-3">No attachments.</li>
                    @endforelse
                </ul>
            </x-ob::card>
        </aside>
    </div>

    <x-ob::modal wire:model="showTriage" title="Convert to task" description="The report, diagnostics and screenshots stay linked to the task." size="lg">
        <form id="triage-form" wire:submit="promoteToTask" class="space-y-4">
            <x-ob::input label="Task title" wire:model="triageTitle" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ob::select label="Project" wire:model="triageProjectId" :options="$projectOptions" required />
                <x-ob::select label="Assignee" wire:model="triageAssigneeId" placeholder="Unassigned" :options="$assigneeOptions" />
                <x-ob::select label="Type" wire:model="triageType" :options="$typeOptions" required />
                <x-ob::select label="Difficulty" wire:model="triageDifficulty" placeholder="Not rated" :options="$difficultyOptions" />
            </div>
        </form>

        <x-slot:footer>
            <x-ob::button variant="ghost" wire:click="$set('showTriage', false)">Cancel</x-ob::button>
            <x-ob::button type="submit" form="triage-form" variant="primary">Add to backlog</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>
</div>
