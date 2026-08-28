<div class="space-y-4 p-6">
    <h1 class="text-2xl font-semibold">Reports</h1>
    <div class="flex gap-3">
        <flux:input wire:model.live.debounce.400ms="q" placeholder="Search..." />
        <flux:select wire:model.live="status" placeholder="Any status">
            <flux:select.option value="">Any status</flux:select.option>
            <flux:select.option value="new">New</flux:select.option>
            <flux:select.option value="in_progress">In progress</flux:select.option>
            <flux:select.option value="done">Done</flux:select.option>
            <flux:select.option value="wontfix">Wontfix</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="source" placeholder="Any source">
            <flux:select.option value="">Any source</flux:select.option>
            <flux:select.option value="ahp">AHP</flux:select.option>
            <flux:select.option value="takeoff">Takeoff</flux:select.option>
        </flux:select>
    </div>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Created</flux:table.column>
            <flux:table.column>Source</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Severity</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Reporter</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($reports as $r)
                <flux:table.row>
                    <flux:table.cell>{{ $r->created_at->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell>{{ $r->source_slug }}</flux:table.cell>
                    <flux:table.cell>{{ $r->type }}</flux:table.cell>
                    <flux:table.cell>{{ $r->severity }}</flux:table.cell>
                    <flux:table.cell>{{ $r->status }}</flux:table.cell>
                    <flux:table.cell>
                        <a class="underline" href="{{ route('reports.show', $r) }}">{{ $r->title }}</a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $r->reporter_email }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">No reports yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    {{ $reports->links() }}
</div>
