@php
    $statusOptions = [
        ['value' => 'new', 'label' => 'New'],
        ['value' => 'converted', 'label' => 'Converted to task'],
        ['value' => 'in_progress', 'label' => 'In progress'],
        ['value' => 'done', 'label' => 'Done'],
        ['value' => 'wontfix', 'label' => 'Won’t fix'],
    ];
    $sourceOptions = $sources->map(fn ($source) => ['value' => $source, 'label' => str($source)->headline()->toString()])->all();
@endphp

<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::page-header title="Reports" />

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <x-ob::stat label="Waiting for triage" :value="number_format($newCount)" icon="inbox-arrow-down" :selected="$status === 'new'" wire:click="$set('status', 'new')" />
        <x-ob::stat label="Converted to tasks" :value="number_format($convertedCount)" icon="check-circle" :selected="$status === 'converted'" wire:click="$set('status', 'converted')" />
        <x-ob::stat label="Dismissed" :value="number_format($dismissedCount)" icon="archive-box" :selected="$status === 'wontfix'" wire:click="$set('status', 'wontfix')" />
    </div>

    <div class="mb-4 grid gap-3 md:grid-cols-[minmax(16rem,1fr)_12rem_12rem]">
        <x-ob::input icon="magnifying-glass" type="search" wire:model.live.debounce.400ms="q" placeholder="Search reports" aria-label="Search reports" />
        <x-ob::select wire:model.live="status" placeholder="Any status" :options="$statusOptions" aria-label="Filter by status" />
        <x-ob::select wire:model.live="source" placeholder="Any project" :options="$sourceOptions" aria-label="Filter by project" />
    </div>

    @if ($reports->isEmpty())
        <x-ob::card :padding="false">
            <x-ob::empty-state icon="inbox" heading="Nothing waiting here">Try changing the filters, or enjoy the rare quiet moment.</x-ob::empty-state>
        </x-ob::card>
    @else
        <x-ob::table>
            <x-slot:head>
                <x-ob::table.head class="w-[36%]">Report</x-ob::table.head>
                <x-ob::table.head class="w-[12%]">Project</x-ob::table.head>
                <x-ob::table.head class="w-[8%]">Type</x-ob::table.head>
                <x-ob::table.head class="w-[9%]">Priority</x-ob::table.head>
                <x-ob::table.head class="w-[11%]">Status</x-ob::table.head>
                <x-ob::table.head class="w-[14%]">Reporter</x-ob::table.head>
                <x-ob::table.head class="w-[10%]">Received</x-ob::table.head>
            </x-slot:head>

            @foreach ($reports as $report)
                @php
                    $severityColor = match ($report->severity) {
                        'blocker' => 'danger',
                        'high' => 'warning',
                        'med' => 'info',
                        default => 'neutral',
                    };
                    $statusColor = match ($report->status) {
                        'done' => 'success',
                        'in_progress' => 'info',
                        'wontfix' => 'neutral',
                        default => 'accent',
                    };
                @endphp
                <x-ob::table.row :href="route('reports.show', $report)" wire:key="report-{{ $report->id }}" wire:transition>
                    <x-ob::table.cell mobile="primary">
                        <span class="font-medium">{{ $report->title }}</span>
                        <span class="block truncate text-xs text-fg-3">{{ $report->description ?: 'No description provided' }}</span>
                    </x-ob::table.cell>
                    <x-ob::table.cell mobile="meta">{{ str($report->source_slug)->headline() }}</x-ob::table.cell>
                    <x-ob::table.cell mobile="hide"><x-ob::badge :color="$report->type === 'bug' ? 'danger' : 'info'">{{ str($report->type)->headline() }}</x-ob::badge></x-ob::table.cell>
                    <x-ob::table.cell mobile="meta"><x-ob::badge :color="$severityColor">{{ str($report->severity)->headline() }}</x-ob::badge></x-ob::table.cell>
                    <x-ob::table.cell mobile="meta" class="relative z-10">
                        @if ($report->task)
                            <a href="{{ route('tasks.show', $report->task) }}" wire:navigate class="focus-ring rounded-full"><x-ob::badge color="success">Task #{{ $report->task->id }}</x-ob::badge></a>
                        @else
                            <x-ob::badge :color="$statusColor">{{ str($report->status)->replace('_', ' ')->ucfirst() }}</x-ob::badge>
                        @endif
                    </x-ob::table.cell>
                    <x-ob::table.cell mobile="hide">{{ $report->reporter_name ?: ($report->reporter_email ?: 'Anonymous') }}</x-ob::table.cell>
                    <x-ob::table.cell mobile="meta" title="{{ $report->created_at }}">{{ $report->created_at->diffForHumans() }}</x-ob::table.cell>
                </x-ob::table.row>
            @endforeach
        </x-ob::table>

        @if ($reports->hasPages())
            <div class="mt-4">
                {{ $reports->links() }}
            </div>
        @endif
    @endif
</div>
