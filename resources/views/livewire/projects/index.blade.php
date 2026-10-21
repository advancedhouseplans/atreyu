<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::page-header title="Projects">
        <x-slot:actions>
            <x-button class="btn-primary rounded-full px-5 shadow-sm" icon="o-plus" wire:click="$set('showCreate', true)" label="New project" />
        </x-slot:actions>
    </x-ob::page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($projects as $project)
            <a href="{{ route('projects.show', $project) }}" wire:navigate wire:key="project-{{ $project->id }}" class="card rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-34px_rgba(0,0,0,0.55)] ring-1 ring-base-content/5 backdrop-blur-xl transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_22px_55px_-30px_rgba(0,0,0,0.6)]">
                <div class="card-body gap-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="grid size-11 place-items-center rounded-box bg-primary/10 text-primary"><x-icon name="o-folder" class="size-6" /></div>
                        <span class="badge badge-ghost">{{ $project->tasks_count }} {{ Str::plural('task', $project->tasks_count) }}</span>
                    </div>
                    <div>
                        <h2 class="card-title">{{ $project->name }}</h2>
                        <p class="mt-2 line-clamp-2 text-sm text-base-content/55">{{ $project->description ?: 'No description yet.' }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="hero min-h-72 rounded-3xl border border-dashed border-base-content/15 bg-base-100/70 shadow-[0_18px_50px_-38px_rgba(0,0,0,0.45)] backdrop-blur-xl md:col-span-2 xl:col-span-3">
                <div class="hero-content text-center">
                    <div>
                        <x-icon name="o-folder-plus" class="mx-auto size-12 text-base-content/30" />
                        <h2 class="mt-4 text-xl font-semibold">Create the first project</h2>
                        <p class="mt-2 text-sm text-base-content/55">Start with Internal, or promote a report to create its source project automatically.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <x-modal wire:model="showCreate" title="New project" subtitle="A home for related tasks and reports">
        <form wire:submit="createProject" class="space-y-4">
            <x-input label="Name" wire:model="name" placeholder="Internal" required autofocus />
            <x-textarea label="Description" wire:model="description" rows="4" placeholder="Work that keeps Advanced House Plans moving." />
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" class="btn-ghost" wire:click="$set('showCreate', false)" label="Cancel" />
                <x-button type="submit" class="btn-primary" spinner="createProject" label="Create project" />
            </div>
        </form>
    </x-modal>
</div>
