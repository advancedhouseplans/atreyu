<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::page-header title="Projects">
        <x-slot:actions>
            <x-ob::button variant="primary" icon="plus" wire:click="$set('showCreate', true)">New project</x-ob::button>
        </x-slot:actions>
    </x-ob::page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($projects as $project)
            @php
                $projectConnected = $project->connection?->last_seen_at?->isAfter(now()->subMinutes(10)) && $project->connection->isActive();
                $connectionLabel = match (true) {
                    ! $project->connection => 'Not connected',
                    (bool) $project->connection->revoked_at => 'Revoked',
                    $projectConnected => 'Connected',
                    default => 'Waiting',
                };
                $connectionColor = match ($connectionLabel) {
                    'Connected' => 'success',
                    'Revoked' => 'danger',
                    default => 'neutral',
                };
            @endphp
            <a href="{{ route('projects.show', $project) }}" wire:navigate wire:key="project-{{ $project->id }}" class="group rounded-xl focus-ring">
                <x-ob::card class="h-full space-y-4 transition duration-120 ease-ob group-hover:bg-surface-3">
                    <div class="flex items-start justify-between gap-4">
                        <span class="grid size-10 place-items-center rounded-lg bg-accent-soft text-accent-fg"><x-ob::icon name="folder" class="size-5" /></span>
                        <div class="flex flex-wrap justify-end gap-2">
                            <x-ob::badge :color="$connectionColor" dot>{{ $connectionLabel }}</x-ob::badge>
                            <x-ob::badge>{{ $project->tasks_count }} {{ Str::plural('task', $project->tasks_count) }}</x-ob::badge>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold">{{ $project->name }}</h2>
                        <p class="mt-1.5 line-clamp-2 text-sm text-fg-2">{{ $project->description ?: 'No description yet.' }}</p>
                    </div>
                </x-ob::card>
            </a>
        @empty
            <x-ob::card :padding="false" class="md:col-span-2 xl:col-span-3">
                <x-ob::empty-state icon="folder-plus" heading="Create the first project">Start with Internal, or promote a report to create its source project automatically.</x-ob::empty-state>
            </x-ob::card>
        @endforelse
    </div>

    <x-ob::modal wire:model="showCreate" title="New project" description="A home for related tasks and reports">
        <form id="create-project-form" wire:submit="createProject" class="space-y-4">
            <x-ob::input label="Name" wire:model="name" placeholder="Internal" required autofocus />
            <x-ob::textarea label="Description" wire:model="description" rows="4" placeholder="Work that keeps Advanced House Plans moving." />
        </form>

        <x-slot:footer>
            <x-ob::button variant="ghost" wire:click="$set('showCreate', false)">Cancel</x-ob::button>
            <x-ob::button type="submit" form="create-project-form" variant="primary">Create project</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>
</div>
