<div class="p-5 sm:p-7 lg:p-8">
    @php
        $severityClass = match ($report->severity) {
            'blocker' => 'badge-error',
            'high' => 'badge-warning',
            'med' => 'badge-info',
            default => 'badge-neutral',
        };
        $statusClass = match ($report->status) {
            'done' => 'badge-success',
            'in_progress' => 'badge-secondary',
            'wontfix' => 'badge-neutral',
            default => 'badge-primary',
        };
        $typeClass = $report->type === 'bug'
            ? 'badge-error'
            : 'badge-secondary';
    @endphp

    <a href="{{ route('reports.index') }}" wire:navigate class="btn btn-ghost btn-sm -ml-2 mb-5 gap-2 text-base-content/60">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" /></svg>
        Reports
    </a>

    <div class="mb-8 flex flex-col justify-between gap-5 xl:flex-row xl:items-start">
        <div class="min-w-0">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span class="badge badge-neutral uppercase">{{ $report->source_slug }}</span>
                <span class="badge {{ $typeClass }} capitalize">{{ $report->type }}</span>
                <span class="badge {{ $severityClass }} capitalize">{{ $report->severity }}</span>
                <span class="badge {{ $statusClass }}">{{ str($report->status)->replace('_', ' ')->title() }}</span>
            </div>
            <h1 class="max-w-4xl text-2xl font-semibold tracking-[-0.025em] sm:text-[1.7rem]">{{ $report->title }}</h1>
            <p class="mt-3 text-sm text-base-content/50">Reported {{ $report->created_at->diffForHumans() }} by {{ $report->reporter_name ?: ($report->reporter_email ?: 'Anonymous reporter') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($report->task)
                <a class="btn btn-success btn-sm" href="{{ route('tasks.show', $report->task) }}" wire:navigate>
                    <x-icon name="o-check-circle" class="size-4" />
                    Open task
                </a>
            @elseif ($report->status === 'wontfix')
                <x-button class="btn-secondary btn-sm" icon="o-arrow-path" wire:click="reopenReport" spinner="reopenReport" label="Reopen" />
            @else
                <x-button class="btn-success btn-sm" icon="o-inbox-arrow-down" wire:click="openTriage" label="Triage report" />
            @endif

            <details class="dropdown dropdown-end">
                <summary class="btn btn-secondary btn-sm">Status: {{ str($report->status)->replace('_', ' ')->title() }}</summary>
                <ul class="menu dropdown-content z-20 mt-2 w-48 rounded-2xl border border-base-content/5 bg-base-100/80 p-2 shadow-2xl ring-1 ring-base-content/5 backdrop-blur-2xl">
                    @foreach (['new', 'in_progress', 'done', 'wontfix'] as $status)
                        <li><button type="button" wire:click="updateStatus('{{ $status }}')">{{ str($status)->replace('_', ' ')->title() }}</button></li>
                    @endforeach
                </ul>
            </details>

            <details class="dropdown dropdown-end">
                <summary class="btn btn-primary btn-sm">{{ $report->assignee?->name ?? 'Assign report' }}</summary>
                <ul class="menu dropdown-content z-20 mt-2 max-h-72 w-56 flex-nowrap overflow-y-auto rounded-2xl border border-base-content/5 bg-base-100/80 p-2 shadow-2xl ring-1 ring-base-content/5 backdrop-blur-2xl">
                    <li><button type="button" wire:click="updateAssignee(null)">Unassigned</button></li>
                    @foreach ($users as $user)
                        <li><button type="button" wire:click="updateAssignee({{ $user->id }})">{{ $user->name }}</button></li>
                    @endforeach
                </ul>
            </details>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <section class="card rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
                <div class="card-body">
                    <h2 class="card-title text-lg">Description</h2>
                    <div class="whitespace-pre-wrap text-sm leading-6 text-base-content/75">{{ $report->description ?: 'No description was provided.' }}</div>
                </div>
            </section>

            <section class="card rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
                <div class="card-body">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="card-title text-lg">Diagnostic timeline</h2>
                        <span class="badge badge-ghost">{{ count($timeline) }} events</span>
                    </div>
                    @forelse ($timeline as $row)
                        <div class="collapse-arrow collapse border-b border-base-content/10 last:border-0">
                            <input type="checkbox" />
                            <div class="collapse-title flex items-center gap-3 px-0 text-sm font-medium">
                                <span class="badge badge-neutral badge-sm">{{ $row['kind'] }}</span>
                                <span class="truncate">{{ $row['message'] ?? $row['url'] ?? $row['route_name'] ?? 'Captured context' }}</span>
                            </div>
                            <div class="collapse-content px-0">
                                <pre class="overflow-x-auto rounded-box bg-base-300 p-4 text-xs"><code>{{ json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-box bg-base-200 p-6 text-center text-sm text-base-content/50">No diagnostic events were captured.</div>
                    @endforelse
                </div>
            </section>

            <section class="card rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
                <div class="card-body">
                    <h2 class="card-title text-lg">Notes</h2>
                    <div class="space-y-3">
                        @forelse ($report->notes as $note)
                            <div class="rounded-box bg-base-200 p-4">
                                <div class="mb-2 flex items-center justify-between gap-3 text-xs text-base-content/45">
                                    <span class="font-semibold text-base-content/70">{{ $note->author->name }}</span>
                                    <span>{{ $note->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="prose prose-sm max-w-none text-base-content/80">{!! \Illuminate\Support\Str::markdown($note->body) !!}</div>
                            </div>
                        @empty
                            <div class="text-sm text-base-content/50">No notes yet.</div>
                        @endforelse
                    </div>

                    <form wire:submit="addNote" class="mt-2 space-y-3">
                        <textarea class="textarea textarea-bordered min-h-24 w-full" wire:model="newNote" placeholder="Add a note for the team..."></textarea>
                        <div class="flex justify-end">
                            <button class="btn btn-primary btn-sm" type="submit" wire:loading.attr="disabled">Post note</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
                <div class="card-body p-5">
                    <h2 class="card-title text-base">Report context</h2>
                    <dl class="space-y-4 text-sm">
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Environment</dt><dd class="mt-1 capitalize">{{ $report->environment }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Page</dt><dd class="mt-1 break-all"><a class="link link-primary" href="{{ $report->url }}" target="_blank" rel="noreferrer">{{ $report->url }}</a></dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Browser</dt><dd class="mt-1">{{ trim("{$report->browser} {$report->browser_version}") ?: 'Unknown' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Operating system</dt><dd class="mt-1">{{ trim("{$report->os} {$report->os_version}") ?: 'Unknown' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Viewport</dt><dd class="mt-1">{{ $report->viewport_wh ?: 'Unknown' }} @ {{ $report->dpr ?: 1 }}x</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Reporter</dt><dd class="mt-1 break-all">{{ $report->reporter_name ?: 'Anonymous reporter' }}@if ($report->reporter_email)<br><span class="text-base-content/50">{{ $report->reporter_email }}</span>@endif</dd></div>
                    </dl>
                </div>
            </section>

            <section class="card rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
                <div class="card-body p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="card-title text-base">Attachments</h2>
                        <span class="badge badge-ghost badge-sm">{{ $report->attachments->count() }}</span>
                    </div>
                    <ul class="space-y-2 text-sm">
                        @forelse ($report->attachments as $attachment)
                            <li>
                                <a class="flex items-center gap-3 rounded-box bg-base-200 p-3 transition-colors hover:bg-base-300" href="{{ route('reports.attachments.download', [$report, $attachment]) }}">
                                    <svg class="size-5 shrink-0 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 12.5 13.5 7a3 3 0 0 1 4.24 4.24l-7.07 7.07a5 5 0 0 1-7.07-7.07l7.07-7.07" stroke-linecap="round" /></svg>
                                    <span class="min-w-0"><span class="block truncate font-medium">{{ str($attachment->kind)->replace('_', ' ')->title() }}</span><span class="text-xs text-base-content/45">{{ $attachment->mime }} · {{ \Illuminate\Support\Number::fileSize($attachment->size) }}</span></span>
                                </a>
                            </li>
                        @empty
                            <li class="text-base-content/50">No attachments.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </aside>
    </div>

    @php
        $matchingProject = $projects->firstWhere('slug', $report->source_slug);
        $projectOptions = $projects->map(fn ($project) => ['id' => (string) $project->id, 'name' => $project->name]);
        if (! $matchingProject) {
            $projectOptions->prepend(['id' => 'source', 'name' => 'Create '.str($report->source_slug)->headline().' project']);
        }
        $userOptions = $users->map(fn ($user) => ['id' => (string) $user->id, 'name' => $user->name]);
        $typeOptions = collect($taskTypes)->map(fn ($type) => ['id' => $type->value, 'name' => $type->label()]);
        $difficultyOptions = collect($taskDifficulties)->map(fn ($difficulty) => ['id' => (string) $difficulty->value, 'name' => $difficulty->label()]);
    @endphp

    <x-modal wire:model="showTriage" title="Turn this report into work" subtitle="Keep the evidence attached while choosing where the task belongs." box-class="max-w-2xl">
        <form wire:submit="promoteToTask" class="space-y-4">
            <x-input label="Task title" wire:model="triageTitle" required autofocus />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select label="Project" wire:model="triageProjectId" :options="$projectOptions" required />
                <x-select label="Assignee" wire:model="triageAssigneeId" placeholder="Unassigned" placeholder-value="" :options="$userOptions" />
                <x-select label="Type" wire:model="triageType" :options="$typeOptions" required />
                <x-select label="Difficulty" wire:model="triageDifficulty" placeholder="Not rated" placeholder-value="" :options="$difficultyOptions" />
            </div>
            <div class="alert alert-info text-sm">
                <x-icon name="o-link" class="size-5" />
                <span>The original report, diagnostics, screenshots, and future context stay linked to the task.</span>
            </div>
            <div class="flex flex-col-reverse justify-between gap-2 pt-2 sm:flex-row">
                <x-button type="button" class="btn-ghost text-error" wire:click="dismissReport" wire:confirm="Dismiss this report without creating a task?" spinner="dismissReport" label="Dismiss report" />
                <div class="flex justify-end gap-2">
                    <x-button type="button" class="btn-ghost" wire:click="$set('showTriage', false)" label="Cancel" />
                    <x-button type="submit" class="btn-primary" spinner="promoteToTask" label="Add to backlog" />
                </div>
            </div>
        </form>
    </x-modal>
</div>
