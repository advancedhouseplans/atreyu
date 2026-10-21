@php
    $connected = $connection?->last_seen_at?->isAfter(now()->subMinutes(10)) && $connection->isActive();
    $assigneeOptions = [
        ['value' => '', 'label' => 'All assignees'],
        ['value' => 'unassigned', 'label' => 'Unassigned'],
        ...$users->map(fn ($user) => ['value' => (string) $user->id, 'label' => $user->name])->all(),
    ];
    $typeColor = fn (string $type) => $type === 'bug' ? 'danger' : 'info';
    $priorityColor = fn (string $kind, string $priority) => match (true) {
        $kind === 'report' && $priority === 'blocker' => 'danger',
        $kind === 'report' && $priority === 'high' => 'warning',
        $kind === 'report' && $priority === 'med' => 'info',
        $kind === 'task' && $priority === 'urgent' => 'danger',
        $kind === 'task' && $priority === 'high' => 'warning',
        $kind === 'task' && $priority === 'normal' => 'info',
        default => 'neutral',
    };
    $statusColor = fn (?string $status) => match ($status) {
        'done' => 'success',
        'in_progress' => 'accent',
        'review' => 'warning',
        default => 'neutral',
    };
@endphp

<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::button variant="ghost" size="sm" icon="chevron-left" :href="route('projects.index')" navigate class="-ml-3 mb-5">Projects</x-ob::button>

    <x-ob::page-header :title="$project->name" :description="$project->description">
        @unless ($connection)
            <x-slot:actions>
                <x-ob::button variant="primary" icon="signal" wire:click="$set('showConnect', true)">Connect app</x-ob::button>
            </x-slot:actions>
        @endunless
    </x-ob::page-header>

    @if ($connection)
        <x-ob::card class="mb-6 space-y-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-accent-soft text-accent-fg">
                        <x-ob::icon name="signal" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold">App connection</h2>
                            <x-ob::badge :color="$connection->revoked_at ? 'danger' : ($connected ? 'success' : 'neutral')" dot>
                                {{ $connection->revoked_at ? 'Revoked' : ($connected ? 'Connected' : 'Waiting') }}
                            </x-ob::badge>
                        </div>
                        <p class="mt-1 text-sm text-fg-3">{{ ucfirst($connection->environment) }} · {{ $connection->reports_count }} {{ Str::plural('report', $connection->reports_count) }}</p>
                    </div>
                </div>

                <x-ob::dropdown>
                    <x-slot:trigger>
                        <x-ob::button variant="ghost" icon="ellipsis-vertical" label="Connection actions" />
                    </x-slot:trigger>

                    <x-ob::menu.item icon="arrow-path" wire:click="rotateConnection" wire:confirm="Rotate this credential? The current key will stop working immediately.">
                        {{ $connection->revoked_at ? 'Reconnect and rotate key' : 'Rotate key' }}
                    </x-ob::menu.item>
                    @unless ($connection->revoked_at)
                        <x-ob::menu.item icon="no-symbol" variant="danger" wire:click="revokeConnection" wire:confirm="Revoke this connection? Reports and heartbeats from it will stop immediately.">Revoke</x-ob::menu.item>
                    @endunless
                </x-ob::dropdown>
            </div>

            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-xs font-medium text-fg-3">Key ID</dt><dd class="mt-1 truncate font-mono text-xs">{{ $connection->key_id }}</dd></div>
                <div><dt class="text-xs font-medium text-fg-3">Last heartbeat</dt><dd class="mt-1">{{ $connection->last_seen_at?->diffForHumans() ?? 'Not received yet' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-medium text-fg-3">Endpoint</dt><dd class="mt-1 truncate">{{ $connection->endpoint ?: 'Discovered on first heartbeat' }}</dd></div>
                <div><dt class="text-xs font-medium text-fg-3">Package</dt><dd class="mt-1">{{ $connection->package_version ?: '—' }}</dd></div>
                <div><dt class="text-xs font-medium text-fg-3">App version</dt><dd class="mt-1">{{ $connection->app_version ?: '—' }}</dd></div>
            </dl>
        </x-ob::card>
    @else
        <x-ob::card :padding="false" class="mb-6">
            <x-ob::empty-state icon="signal-slash" heading="Not connected">
                Connect this project to its Laravel app to receive heartbeats and reports.
                <x-slot:action>
                    <x-ob::button variant="primary" icon="signal" wire:click="$set('showConnect', true)">Connect app</x-ob::button>
                </x-slot:action>
            </x-ob::empty-state>
        </x-ob::card>
    @endif

    <x-ob::card class="mb-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-surface-3 text-fg-2">
                    <x-ob::icon name="code-bracket" class="size-5" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold">Repository</h2>
                    @if ($project->repository_url)
                        <a href="{{ $project->repository_url }}" target="_blank" rel="noreferrer" class="mt-1 inline-flex items-center gap-1 truncate rounded text-sm text-accent-fg hover:underline focus-ring">
                            {{ $project->repositoryLabel() }}
                            <x-ob::icon name="arrow-top-right-on-square" class="size-3.5" />
                        </a>
                    @else
                        <p class="mt-1 text-sm text-fg-3">Add a link to this project's code repository.</p>
                    @endif
                </div>
            </div>
            <x-ob::button variant="ghost" size="sm" icon="{{ $project->repository_url ? 'pencil-square' : 'plus' }}" wire:click="$set('showRepository', true)">
                {{ $project->repository_url ? 'Edit' : 'Add repository' }}
            </x-ob::button>
        </div>
    </x-ob::card>

    <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <x-ob::segmented
            name="project-tab"
            label="Project records"
            wire:model.live="tab"
            :options="[
                ['value' => 'reports', 'label' => 'Reports'],
                ['value' => 'tasks', 'label' => 'Tasks'],
            ]"
        />
        @if ($tab === 'tasks')
            <x-ob::select wire:model.live="taskAssignee" :options="$assigneeOptions" aria-label="Filter tasks by assignee" class="w-full sm:w-52" />
        @endif
    </div>

    @if ($workItems->isEmpty())
        <x-ob::card :padding="false">
            @if ($tab === 'tasks')
                <x-ob::empty-state icon="check-circle" heading="No tasks yet">Tasks created for this project will appear here.</x-ob::empty-state>
            @else
                <x-ob::empty-state icon="inbox" heading="No reports yet">Reports received from this project will appear here.</x-ob::empty-state>
            @endif
        </x-ob::card>
    @else
        @if ($tab === 'tasks')
            <x-ob::table>
                <x-slot:head>
                    <x-ob::table.head class="w-[40%]">Task</x-ob::table.head>
                    <x-ob::table.head class="w-[10%]">Type</x-ob::table.head>
                    <x-ob::table.head class="w-[12%]">Priority</x-ob::table.head>
                    <x-ob::table.head class="w-[14%]">Status</x-ob::table.head>
                    <x-ob::table.head class="w-[14%]">Assignee</x-ob::table.head>
                    <x-ob::table.head class="w-[10%]">Updated</x-ob::table.head>
                </x-slot:head>

                @foreach ($workItems as $item)
                    <x-ob::table.row :href="route('tasks.show', $item->id)" wire:key="project-task-{{ $item->id }}" wire:transition>
                        <x-ob::table.cell mobile="primary">
                            <span class="block font-mono text-[11px] text-fg-3">AT-{{ $item->id }}@if ($item->archived_at) · Archived @endif</span>
                            <span class="font-medium">{{ $item->title }}</span>
                            @if ($item->description)
                                <span class="block truncate text-xs text-fg-3">{{ $item->description }}</span>
                            @endif
                        </x-ob::table.cell>
                        <x-ob::table.cell mobile="hide"><x-ob::badge :color="$typeColor($item->type)">{{ str($item->type)->headline() }}</x-ob::badge></x-ob::table.cell>
                        <x-ob::table.cell mobile="meta"><x-ob::badge :color="$priorityColor('task', $item->priority)">{{ str($item->priority)->headline() }}</x-ob::badge></x-ob::table.cell>
                        <x-ob::table.cell mobile="meta"><x-ob::badge :color="$statusColor($item->status)">{{ str($item->status)->replace('_', ' ')->headline() }}</x-ob::badge></x-ob::table.cell>
                        <x-ob::table.cell mobile="hide">{{ $item->person }}</x-ob::table.cell>
                        <x-ob::table.cell mobile="meta" title="{{ $item->sort_date }}">{{ \Illuminate\Support\Carbon::parse($item->sort_date)->diffForHumans() }}</x-ob::table.cell>
                    </x-ob::table.row>
                @endforeach
            </x-ob::table>
        @else
            <x-ob::table>
                <x-slot:head>
                    <x-ob::table.head class="w-[46%]">Report</x-ob::table.head>
                    <x-ob::table.head class="w-[12%]">Type</x-ob::table.head>
                    <x-ob::table.head class="w-[12%]">Priority</x-ob::table.head>
                    <x-ob::table.head class="w-[20%]">Reporter</x-ob::table.head>
                    <x-ob::table.head class="w-[10%]">Received</x-ob::table.head>
                </x-slot:head>

                @foreach ($workItems as $item)
                    <x-ob::table.row :href="route('reports.show', $item->id)" wire:key="project-report-{{ $item->id }}" wire:transition>
                        <x-ob::table.cell mobile="primary">
                            <span class="block font-mono text-[11px] text-fg-3">Report #{{ $item->id }}@if ($item->archived_at) · Archived @endif</span>
                            <span class="font-medium">{{ $item->title }}</span>
                            @if ($item->description)
                                <span class="block truncate text-xs text-fg-3">{{ $item->description }}</span>
                            @endif
                        </x-ob::table.cell>
                        <x-ob::table.cell mobile="hide"><x-ob::badge :color="$typeColor($item->type)">{{ str($item->type)->headline() }}</x-ob::badge></x-ob::table.cell>
                        <x-ob::table.cell mobile="meta"><x-ob::badge :color="$priorityColor('report', $item->priority)">{{ str($item->priority)->headline() }}</x-ob::badge></x-ob::table.cell>
                        <x-ob::table.cell mobile="hide">{{ $item->person }}</x-ob::table.cell>
                        <x-ob::table.cell mobile="meta" title="{{ $item->sort_date }}">{{ \Illuminate\Support\Carbon::parse($item->sort_date)->diffForHumans() }}</x-ob::table.cell>
                    </x-ob::table.row>
                @endforeach
            </x-ob::table>
        @endif

        @if ($workItems->hasPages())
            <div class="mt-4">{{ $workItems->links() }}</div>
        @endif
    @endif

    <x-ob::modal wire:model="showConnect" :title="'Connect '.$project->name" description="Each project has one revocable app connection">
        <form id="create-connection-form" wire:submit="createConnection" class="space-y-4">
            <x-ob::select label="Environment" wire:model="environment" :options="[
                ['value' => 'local', 'label' => 'Local'],
                ['value' => 'staging', 'label' => 'Staging'],
                ['value' => 'production', 'label' => 'Production'],
            ]" />
        </form>

        <x-slot:footer>
            <x-ob::button variant="ghost" wire:click="$set('showConnect', false)">Cancel</x-ob::button>
            <x-ob::button type="submit" form="create-connection-form" variant="primary">Create connection</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>

    <x-ob::modal wire:model.live="showCredential" :title="'Install '.$project->name" description="This credential is shown only now">
        <div class="mb-4 flex items-start gap-3 rounded-lg bg-warning-soft p-3 text-sm text-warning-fg">
            <x-ob::icon name="exclamation-triangle" class="size-5 shrink-0" />
            <span>Copy this before closing. Atreyu stores only an encrypted copy and will not display the credential again.</span>
        </div>
        <p class="mb-2 text-sm font-medium">Add these values to the other app's <code class="font-mono">.env</code>:</p>
        <div class="overflow-x-auto rounded-lg border border-hairline bg-surface-2 p-3 font-mono text-xs">
            <pre><code>ATREYU_URL={{ rtrim(config('app.url'), '/') }}</code></pre>
            <pre><code>ATREYU_KEY={{ $issuedCredential }}</code></pre>
        </div>
        <p class="mt-4 text-sm text-fg-2">Then install the local package and place <code class="font-mono">@@atreyu</code> before the closing <code class="font-mono">&lt;/body&gt;</code> tag in the app layout. The first web request sends the heartbeat.</p>

        <x-slot:footer>
            <x-ob::button variant="primary" wire:click="clearCredential">I've copied it</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>

    <x-ob::modal wire:model="showRepository" title="Project repository" description="A quick link to this project's code">
        <form id="repository-form" wire:submit="saveRepository" class="space-y-4">
            <x-ob::input label="Repository URL" wire:model="repositoryUrl" type="url" placeholder="https://github.com/advancedhouseplans/website" required />
        </form>

        <x-slot:footer>
            @if ($project->repository_url)
                <x-ob::button variant="ghost" class="mr-auto" wire:click="removeRepository" wire:confirm="Remove this repository link?">Remove</x-ob::button>
            @endif
            <x-ob::button variant="ghost" wire:click="$set('showRepository', false)">Cancel</x-ob::button>
            <x-ob::button type="submit" form="repository-form" variant="primary">Save repository</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>
</div>
