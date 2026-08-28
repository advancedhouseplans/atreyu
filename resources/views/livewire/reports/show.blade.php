<div class="space-y-6 p-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">{{ $report->title }}</h1>
        <div class="flex gap-2">
            <flux:badge>{{ $report->type }}</flux:badge>
            <flux:badge>{{ $report->severity }}</flux:badge>
            <flux:dropdown>
                <flux:button size="sm">{{ $report->status }}</flux:button>
                <flux:menu>
                    @foreach (['new', 'in_progress', 'done', 'wontfix'] as $s)
                        <flux:menu.item wire:click="updateStatus('{{ $s }}')">{{ $s }}</flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
            <flux:dropdown>
                <flux:button size="sm">
                    Assignee: {{ $report->assignee?->name ?? '—' }}
                </flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="updateAssignee(null)">Unassigned</flux:menu.item>
                    @foreach ($users as $user)
                        <flux:menu.item wire:click="updateAssignee({{ $user->id }})">{{ $user->name }}</flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div>
            <h2 class="mb-2 text-lg font-semibold">Meta</h2>
            <dl class="space-y-1 text-sm">
                <div><dt class="inline text-neutral-500">Source:</dt> <dd class="inline">{{ $report->source_slug }} ({{ $report->environment }})</dd></div>
                <div><dt class="inline text-neutral-500">URL:</dt> <dd class="inline">{{ $report->url }}</dd></div>
                <div><dt class="inline text-neutral-500">Browser:</dt> <dd class="inline">{{ $report->browser }} {{ $report->browser_version }}</dd></div>
                <div><dt class="inline text-neutral-500">OS:</dt> <dd class="inline">{{ $report->os }} {{ $report->os_version }}</dd></div>
                <div><dt class="inline text-neutral-500">Viewport:</dt> <dd class="inline">{{ $report->viewport_wh }} @ {{ $report->dpr }}x</dd></div>
                <div><dt class="inline text-neutral-500">Reporter:</dt> <dd class="inline">{{ $report->reporter_name }} &lt;{{ $report->reporter_email }}&gt;</dd></div>
            </dl>
            <div class="mt-4 whitespace-pre-wrap text-sm">{{ $report->description }}</div>
        </div>
        <div>
            <h2 class="mb-2 text-lg font-semibold">Attachments</h2>
            <ul class="space-y-1 text-sm">
                @foreach ($report->attachments as $a)
                    <li>
                        <a class="underline" href="{{ route('reports.attachments.download', [$report, $a]) }}">
                            {{ $a->kind }} — {{ $a->mime }} — {{ $a->size }} bytes
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div>
        <h2 class="mb-2 text-lg font-semibold">Timeline</h2>
        <ol class="space-y-1 font-mono text-sm">
            @foreach ($timeline as $row)
                <li>[{{ $row['kind'] }}] {{ json_encode($row, JSON_UNESCAPED_SLASHES) }}</li>
            @endforeach
        </ol>
    </div>

    <div>
        <h2 class="mb-2 text-lg font-semibold">Notes</h2>
        <div class="space-y-2 text-sm">
            @foreach ($report->notes as $note)
                <div class="rounded border p-2">
                    <div class="text-xs text-neutral-500">{{ $note->author->name }} — {{ $note->created_at->diffForHumans() }}</div>
                    <div class="prose prose-sm max-w-none">{!! \Illuminate\Support\Str::markdown($note->body) !!}</div>
                </div>
            @endforeach
        </div>
        <form wire:submit="addNote" class="mt-2 flex gap-2">
            <flux:textarea wire:model="newNote" placeholder="Add a note..." rows="2" />
            <flux:button type="submit">Post</flux:button>
        </form>
    </div>
</div>
