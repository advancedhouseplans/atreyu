<div class="p-5 sm:p-7 lg:p-8">
    <a href="{{ route('projects.index') }}" wire:navigate class="btn btn-ghost btn-sm mb-5 -ml-2">
        <x-icon name="o-arrow-left" class="size-4" />Projects
    </a>

    <x-ob::page-header :title="$project->name">
        <x-slot:actions>
            <a href="{{ route('tasks.index', ['project' => $project->id]) }}" wire:navigate class="btn btn-ghost rounded-full px-5">
                <x-icon name="o-rectangle-stack" class="size-5" />View tasks
            </a>
            <x-button class="btn-primary rounded-full px-5 shadow-sm" icon="o-plus" wire:click="$set('showCreate', true)" label="Connect an app" />
        </x-slot:actions>
    </x-ob::page-header>

    @if ($project->description)
        <p class="-mt-4 mb-7 max-w-3xl text-sm leading-6 text-base-content/60">{{ $project->description }}</p>
    @endif

    <div class="mb-6 rounded-3xl border border-base-content/5 bg-base-100/65 p-5 shadow-[0_16px_45px_-36px_rgba(0,0,0,0.45)] ring-1 ring-base-content/5 backdrop-blur-xl">
        <div class="flex items-start gap-4">
            <div class="grid size-10 shrink-0 place-items-center rounded-box bg-primary text-primary-content">
                <x-icon name="o-signal" class="size-5" />
            </div>
            <div>
                <h2 class="font-semibold">Reporter installations</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-base-content/60">Each app gets one credential. The Laravel package sends a signed heartbeat so Atreyu learns its callback URL, environment, and version automatically—no source config needs to be maintained here.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($installations as $installation)
            @php($connected = $installation->last_seen_at?->isAfter(now()->subMinutes(10)) && $installation->isActive())
            <div wire:key="installation-{{ $installation->id }}" class="card rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-34px_rgba(0,0,0,0.55)] ring-1 ring-base-content/5 backdrop-blur-xl">
                <div class="card-body gap-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="card-title text-lg">{{ $installation->name }}</h2>
                                <span @class(['badge badge-sm', 'badge-success' => $connected, 'badge-error' => $installation->revoked_at, 'badge-ghost' => ! $connected && ! $installation->revoked_at])>
                                    {{ $installation->revoked_at ? 'Revoked' : ($connected ? 'Connected' : 'Waiting') }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-base-content/50">{{ ucfirst($installation->environment) }} · {{ $installation->reports_count }} {{ Str::plural('report', $installation->reports_count) }}</p>
                        </div>
                        <div class="dropdown dropdown-end">
                            <button type="button" tabindex="0" class="btn btn-ghost btn-sm btn-square" aria-label="Installation actions"><x-icon name="o-ellipsis-vertical" class="size-5" /></button>
                            <ul tabindex="0" class="menu dropdown-content z-20 mt-2 w-44 rounded-2xl border border-base-content/5 bg-base-100/80 p-2 shadow-2xl ring-1 ring-base-content/5 backdrop-blur-2xl">
                                <li><button type="button" wire:click="rotateInstallation({{ $installation->id }})" wire:confirm="Rotate this credential? The current key will stop working immediately.">Rotate key</button></li>
                                @if (! $installation->revoked_at)
                                    <li><button type="button" class="text-error" wire:click="revokeInstallation({{ $installation->id }})" wire:confirm="Revoke this installation? Reports and heartbeats from it will stop immediately.">Revoke</button></li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Key ID</dt><dd class="mt-1 truncate font-mono text-xs">{{ $installation->key_id }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Last heartbeat</dt><dd class="mt-1">{{ $installation->last_seen_at?->diffForHumans() ?? 'Not received yet' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Endpoint</dt><dd class="mt-1 truncate">{{ $installation->endpoint ?: 'Discovered on first heartbeat' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">Package</dt><dd class="mt-1">{{ $installation->package_version ?: '—' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-base-content/40">App version</dt><dd class="mt-1">{{ $installation->app_version ?: '—' }}</dd></div>
                    </dl>
                </div>
            </div>
        @empty
            <div class="hero min-h-72 rounded-3xl border border-dashed border-base-content/15 bg-base-100/70 shadow-[0_18px_50px_-38px_rgba(0,0,0,0.45)] backdrop-blur-xl lg:col-span-2">
                <div class="hero-content text-center">
                    <div>
                        <x-icon name="o-signal-slash" class="mx-auto size-12 text-base-content/30" />
                        <h2 class="mt-4 text-xl font-semibold">No apps connected yet</h2>
                        <p class="mt-2 text-sm text-base-content/55">Create an installation to get the two environment values for your Laravel app.</p>
                        <x-button class="btn-primary mt-5" icon="o-plus" wire:click="$set('showCreate', true)" label="Connect the first app" />
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <x-modal wire:model="showCreate" title="Connect a Laravel app" subtitle="Create a revocable credential for one deployed environment">
        <form wire:submit="createInstallation" class="space-y-4">
            <x-input label="Installation name" wire:model="name" placeholder="Clara on Jamie's Mac" required autofocus />
            <x-select label="Environment" wire:model="environment" :options="[
                ['id' => 'local', 'name' => 'Local'],
                ['id' => 'staging', 'name' => 'Staging'],
                ['id' => 'production', 'name' => 'Production'],
            ]" />
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" class="btn-ghost" wire:click="$set('showCreate', false)" label="Cancel" />
                <x-button type="submit" class="btn-primary" spinner="createInstallation" label="Create credential" />
            </div>
        </form>
    </x-modal>

    <x-modal wire:model="showCredential" title="Install {{ $issuedName }}" subtitle="This credential is shown only now">
        <div class="alert alert-warning mb-4 text-sm">
            <x-icon name="o-exclamation-triangle" class="size-5" />
            <span>Copy this before closing. Atreyu stores only an encrypted copy and will not display the credential again.</span>
        </div>
        <p class="mb-2 text-sm font-medium">Add these values to the other app's <code>.env</code>:</p>
        <div class="mockup-code overflow-x-auto text-xs">
            <pre><code>ATREYU_URL={{ rtrim(config('app.url'), '/') }}</code></pre>
            <pre><code>ATREYU_KEY={{ $issuedCredential }}</code></pre>
        </div>
        <p class="mt-4 text-sm text-base-content/60">Then install the local package and place <code>@@atreyu</code> before the closing <code>&lt;/body&gt;</code> tag in the app layout. The first web request sends the heartbeat.</p>
        <div class="mt-5 flex justify-end"><x-button class="btn-primary" wire:click="clearCredential" label="I've copied it" /></div>
    </x-modal>
</div>
