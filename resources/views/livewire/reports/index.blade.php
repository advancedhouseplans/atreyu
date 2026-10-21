@php
    $viewOptions = [
        ['value' => 'inbox', 'label' => 'Inbox'],
        ['value' => 'triaged', 'label' => 'Triaged'],
        ['value' => 'archived', 'label' => 'Archived'],
        ['value' => 'all', 'label' => 'All reports'],
    ];
    $sourceOptions = $sources->map(fn ($source) => ['value' => $source, 'label' => str($source)->headline()->toString()])->all();
@endphp

<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::page-header title="Reports" />

    <x-ob::card class="mb-6">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold">Report activity</h2>
                <p class="mt-0.5 text-xs text-fg-3">{{ number_format($activityTotal) }} {{ str('report')->plural($activityTotal) }} received in {{ $activityYear }}</p>
            </div>
            <div class="flex items-center gap-1.5 text-[11px] text-fg-3" aria-label="Activity scale from fewer to more reports">
                <span>Less</span>
                <span class="size-2.5 rounded-[3px] bg-surface-3"></span>
                <span class="size-2.5 rounded-[3px] bg-accent/25"></span>
                <span class="size-2.5 rounded-[3px] bg-accent/45"></span>
                <span class="size-2.5 rounded-[3px] bg-accent/70"></span>
                <span class="size-2.5 rounded-[3px] bg-accent"></span>
                <span>More</span>
            </div>
        </div>

        <div class="overflow-x-auto pb-1">
            <div class="w-max min-w-full">
                <div class="mb-1.5 grid grid-cols-[repeat(53,0.625rem)] gap-1 text-[10px] leading-none text-fg-3" aria-hidden="true">
                    @foreach ($activityMonths as $month)
                        <span class="whitespace-nowrap" style="grid-column-start: {{ $month['column'] }}">{{ $month['label'] }}</span>
                    @endforeach
                </div>

                <div class="grid w-max grid-flow-col grid-rows-7 gap-1" role="img" aria-label="Report activity for {{ $activityYear }}">
                    @foreach ($activityDays as $day)
                        @php
                            $activityClass = match ($day['level']) {
                                1 => 'bg-accent/25',
                                2 => 'bg-accent/45',
                                3 => 'bg-accent/70',
                                4 => 'bg-accent',
                                default => 'bg-surface-3',
                            };
                        @endphp
                        <span
                            data-report-day="{{ $day['date'] }}"
                            data-level="{{ $day['level'] }}"
                            class="size-2.5 rounded-[3px] {{ $activityClass }}"
                            title="{{ $day['label'] }} · {{ $day['count'] }} {{ str('report')->plural($day['count']) }}"
                            aria-hidden="true"
                        ></span>
                    @endforeach
                </div>
            </div>
        </div>
    </x-ob::card>

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <x-ob::stat label="Inbox" :value="number_format($inboxCount)" icon="inbox-arrow-down" :selected="$view === 'inbox'" wire:click="$set('view', 'inbox')" />
        <x-ob::stat label="Triaged" :value="number_format($triagedCount)" icon="check-circle" :selected="$view === 'triaged'" wire:click="$set('view', 'triaged')" />
        <x-ob::stat label="Archived" :value="number_format($archivedCount)" icon="archive-box" :selected="$view === 'archived'" wire:click="$set('view', 'archived')" />
    </div>

    <div class="mb-4 grid gap-3 md:grid-cols-[minmax(16rem,1fr)_12rem_12rem]">
        <x-ob::input icon="magnifying-glass" type="search" wire:model.live.debounce.400ms="q" placeholder="Search reports" aria-label="Search reports" />
        <x-ob::select wire:model.live="view" :options="$viewOptions" aria-label="Filter reports" />
        <x-ob::select wire:model.live="source" placeholder="Any project" :options="$sourceOptions" aria-label="Filter by project" />
    </div>

    @if ($reports->isEmpty())
        <x-ob::card :padding="false">
            <x-ob::empty-state icon="inbox" heading="Nothing waiting here">Try changing the filters, or enjoy the rare quiet moment.</x-ob::empty-state>
        </x-ob::card>
    @else
        <x-ob::table>
            <x-slot:head>
                <x-ob::table.head class="w-[38%]">Report</x-ob::table.head>
                <x-ob::table.head class="w-[12%]">Project</x-ob::table.head>
                <x-ob::table.head class="w-[8%]">Type</x-ob::table.head>
                <x-ob::table.head class="w-[9%]">Priority</x-ob::table.head>
                <x-ob::table.head class="w-[9%]">Task</x-ob::table.head>
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
                @endphp
                <x-ob::table.row :href="route('reports.show', $report)" wire:key="report-{{ $report->id }}" wire:transition>
                    <x-ob::table.cell mobile="primary">
                        <span class="flex items-center gap-2">
                            <span class="truncate font-medium">{{ $report->title }}</span>
                            @if ($report->occurrences_count)
                                <x-ob::badge>{{ $report->occurrences_count + 1 }} occurrences</x-ob::badge>
                            @endif
                        </span>
                        <span class="block truncate text-xs text-fg-3">{{ $report->description ?: 'No description provided' }}</span>
                    </x-ob::table.cell>
                    <x-ob::table.cell mobile="meta">{{ str($report->source_slug)->headline() }}</x-ob::table.cell>
                    <x-ob::table.cell mobile="hide"><x-ob::badge :color="$report->type === 'bug' ? 'danger' : 'info'">{{ str($report->type)->headline() }}</x-ob::badge></x-ob::table.cell>
                    <x-ob::table.cell mobile="meta"><x-ob::badge :color="$severityColor">{{ str($report->severity)->headline() }}</x-ob::badge></x-ob::table.cell>
                    <x-ob::table.cell mobile="meta" class="relative z-10">
                        @if ($report->task)
                            <a href="{{ route('tasks.show', $report->task) }}" wire:navigate class="focus-ring rounded-full"><x-ob::badge color="accent">{{ $report->task->identifier }}</x-ob::badge></a>
                        @else
                            <span class="text-fg-3">—</span>
                        @endif
                    </x-ob::table.cell>
                    <x-ob::table.cell mobile="hide">{{ $report->reporter_name ?: ($report->reporter_email ?: 'Anonymous') }}</x-ob::table.cell>
                    @php($lastReceivedAt = $report->occurrences_max_created_at ? \Illuminate\Support\Carbon::parse($report->occurrences_max_created_at) : $report->created_at)
                    <x-ob::table.cell mobile="meta" title="{{ $lastReceivedAt }}">{{ $lastReceivedAt->diffForHumans() }}</x-ob::table.cell>
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
