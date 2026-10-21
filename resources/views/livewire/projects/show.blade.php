<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::button variant="ghost" size="sm" icon="chevron-left" :href="route('projects.index')" navigate class="-ml-3 mb-5">Projects</x-ob::button>

    <x-ob::page-header :title="$project->name" :description="$project->description">
        <x-slot:actions>
            <x-ob::button variant="ghost" icon="rectangle-stack" :href="route('tasks.index', ['project' => $project->id])" navigate>View tasks</x-ob::button>
            <x-ob::button variant="primary" icon="plus" wire:click="$set('showCreate', true)">Connect an app</x-ob::button>
        </x-slot:actions>
    </x-ob::page-header>

    <x-ob::card class="mb-6">
        <div class="flex items-start gap-4">
            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-accent text-on-accent">
                <x-ob::icon name="signal" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-semibold">Reporter installations</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-fg-2">Each app gets one credential. The Laravel package sends a signed heartbeat so Atreyu learns its callback URL, environment, and version automatically—no source config needs to be maintained here.</p>
            </div>
        </div>
    </x-ob::card>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($installations as $installation)
            @php($connected = $installation->last_seen_at?->isAfter(now()->subMinutes(10)) && $installation->isActive())
            <x-ob::card wire:key="installation-{{ $installation->id }}" class="space-y-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold">{{ $installation->name }}</h2>
                            <x-ob::badge :color="$installation->revoked_at ? 'danger' : ($connected ? 'success' : 'neutral')" dot>
                                {{ $installation->revoked_at ? 'Revoked' : ($connected ? 'Connected' : 'Waiting') }}
                            </x-ob::badge>
                        </div>
                        <p class="mt-1 text-sm text-fg-3">{{ ucfirst($installation->environment) }} · {{ $installation->reports_count }} {{ Str::plural('report', $installation->reports_count) }}</p>
                    </div>
                    <x-ob::dropdown>
                        <x-slot:trigger>
                            <x-ob::button variant="ghost" icon="ellipsis-vertical" label="Installation actions" />
                        </x-slot:trigger>

                        <x-ob::menu.item icon="arrow-path" wire:click="rotateInstallation({{ $installation->id }})" wire:confirm="Rotate this credential? The current key will stop working immediately.">Rotate key</x-ob::menu.item>
                        @if (! $installation->revoked_at)
                            <x-ob::menu.item icon="no-symbol" variant="danger" wire:click="revokeInstallation({{ $installation->id }})" wire:confirm="Revoke this installation? Reports and heartbeats from it will stop immediately.">Revoke</x-ob::menu.item>
                        @endif
                    </x-ob::dropdown>
                </div>

                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs font-medium text-fg-3">Key ID</dt><dd class="mt-1 truncate font-mono text-xs">{{ $installation->key_id }}</dd></div>
                    <div><dt class="text-xs font-medium text-fg-3">Last heartbeat</dt><dd class="mt-1">{{ $installation->last_seen_at?->diffForHumans() ?? 'Not received yet' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium text-fg-3">Endpoint</dt><dd class="mt-1 truncate">{{ $installation->endpoint ?: 'Discovered on first heartbeat' }}</dd></div>
                    <div><dt class="text-xs font-medium text-fg-3">Package</dt><dd class="mt-1">{{ $installation->package_version ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-fg-3">App version</dt><dd class="mt-1">{{ $installation->app_version ?: '—' }}</dd></div>
                </dl>
            </x-ob::card>
        @empty
            <x-ob::card :padding="false" class="lg:col-span-2">
                <x-ob::empty-state icon="signal-slash" heading="No apps connected yet">
                    Create an installation to get the two environment values for your Laravel app.
                    <x-slot:action>
                        <x-ob::button variant="primary" icon="plus" wire:click="$set('showCreate', true)">Connect the first app</x-ob::button>
                    </x-slot:action>
                </x-ob::empty-state>
            </x-ob::card>
        @endforelse
    </div>

    <x-ob::modal wire:model="showCreate" title="Connect a Laravel app" description="Create a revocable credential for one deployed environment">
        <form id="create-installation-form" wire:submit="createInstallation" class="space-y-4">
            <x-ob::input label="Installation name" wire:model="name" placeholder="Clara on Jamie's Mac" required autofocus />
            <x-ob::select label="Environment" wire:model="environment" :options="[
                ['value' => 'local', 'label' => 'Local'],
                ['value' => 'staging', 'label' => 'Staging'],
                ['value' => 'production', 'label' => 'Production'],
            ]" />
        </form>

        <x-slot:footer>
            <x-ob::button variant="ghost" wire:click="$set('showCreate', false)">Cancel</x-ob::button>
            <x-ob::button type="submit" form="create-installation-form" variant="primary">Create credential</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>

    <x-ob::modal wire:model.live="showCredential" :title="'Install '.$issuedName" description="This credential is shown only now">
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
</div>
