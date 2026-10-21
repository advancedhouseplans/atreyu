<div>
    <x-ob::command-palette
        wire:model.live.debounce.150ms="query"
        :actions="[
            ['label' => 'New task', 'href' => route('tasks.index').'?create=1', 'icon' => 'plus'],
            ['label' => 'Reports', 'href' => route('reports.index'), 'icon' => 'inbox-stack'],
            ['label' => 'Projects', 'href' => route('projects.index'), 'icon' => 'folder'],
            ['label' => 'Tasks', 'href' => route('tasks.index'), 'icon' => 'check-circle'],
            ['label' => 'Settings', 'href' => route('profile.edit'), 'icon' => 'cog-6-tooth'],
            ['label' => 'Toggle appearance', 'event' => 'ob-theme-cycle', 'icon' => 'swatch'],
        ]"
    >
        @if ($reports->isNotEmpty())
            <x-ob::command-palette.group heading="Reports">
                @foreach ($reports as $report)
                    <x-ob::command-palette.item :href="route('reports.show', $report)" icon="inbox-stack" :meta="$report->created_at->diffForHumans(short: true)">{{ $report->title }}</x-ob::command-palette.item>
                @endforeach
            </x-ob::command-palette.group>
        @endif

        @if ($tasks->isNotEmpty())
            <x-ob::command-palette.group heading="Tasks">
                @foreach ($tasks as $task)
                    <x-ob::command-palette.item :href="route('tasks.show', $task)" icon="check-circle" :meta="$task->identifier">{{ $task->title }}</x-ob::command-palette.item>
                @endforeach
            </x-ob::command-palette.group>
        @endif

        @if ($projects->isNotEmpty())
            <x-ob::command-palette.group heading="Projects">
                @foreach ($projects as $project)
                    <x-ob::command-palette.item :href="route('projects.show', $project)" icon="folder">{{ $project->name }}</x-ob::command-palette.item>
                @endforeach
            </x-ob::command-palette.group>
        @endif
    </x-ob::command-palette>
</div>
