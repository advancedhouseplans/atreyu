<div class="p-5 sm:p-7 lg:p-8" wire:loading.class="opacity-70">
    <x-page-header title="Reports" />

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <button type="button" wire:click="$set('status', 'new')" @class(['rounded-2xl border p-4 text-left shadow-sm transition duration-200 hover:shadow-md', 'border-primary bg-primary text-primary-content' => $status === 'new', 'border-base-content/10 bg-base-100 text-base-content hover:border-base-content/20' => $status !== 'new'])>
            <span class="flex items-center justify-between gap-3">
                <span @class(['grid size-9 place-items-center rounded-xl shadow-sm', 'bg-primary-content/15 text-primary-content' => $status === 'new', 'bg-primary text-primary-content' => $status !== 'new'])><x-icon name="o-inbox-arrow-down" class="size-4" /></span>
                @if ($status === 'new')<span class="text-xs font-semibold text-primary-content/80">Selected</span>@endif
            </span>
            <span class="mt-5 flex items-end justify-between gap-3">
                <span @class(['text-sm font-medium', 'text-primary-content/80' => $status === 'new', 'text-base-content/65' => $status !== 'new'])>Waiting for triage</span>
                <span class="text-3xl font-semibold tracking-[-0.04em]">{{ number_format($newCount) }}</span>
            </span>
        </button>
        <button type="button" wire:click="$set('status', 'converted')" @class(['rounded-2xl border p-4 text-left shadow-sm transition duration-200 hover:shadow-md', 'border-success bg-success text-success-content' => $status === 'converted', 'border-base-content/10 bg-base-100 text-base-content hover:border-base-content/20' => $status !== 'converted'])>
            <span class="flex items-center justify-between gap-3">
                <span @class(['grid size-9 place-items-center rounded-xl shadow-sm', 'bg-success-content/15 text-success-content' => $status === 'converted', 'bg-success text-success-content' => $status !== 'converted'])><x-icon name="o-check-circle" class="size-4" /></span>
                @if ($status === 'converted')<span class="text-xs font-semibold text-success-content/80">Selected</span>@endif
            </span>
            <span class="mt-5 flex items-end justify-between gap-3">
                <span @class(['text-sm font-medium', 'text-success-content/80' => $status === 'converted', 'text-base-content/65' => $status !== 'converted'])>Converted to tasks</span>
                <span class="text-3xl font-semibold tracking-[-0.04em]">{{ number_format($convertedCount) }}</span>
            </span>
        </button>
        <button type="button" wire:click="$set('status', 'wontfix')" @class(['rounded-2xl border p-4 text-left shadow-sm transition duration-200 hover:shadow-md', 'border-neutral bg-neutral text-neutral-content' => $status === 'wontfix', 'border-base-content/10 bg-base-100 text-base-content hover:border-base-content/20' => $status !== 'wontfix'])>
            <span class="flex items-center justify-between gap-3">
                <span @class(['grid size-9 place-items-center rounded-xl shadow-sm', 'bg-neutral-content/15 text-neutral-content' => $status === 'wontfix', 'bg-neutral text-neutral-content' => $status !== 'wontfix'])><x-icon name="o-archive-box" class="size-4" /></span>
                @if ($status === 'wontfix')<span class="text-xs font-semibold text-neutral-content/80">Selected</span>@endif
            </span>
            <span class="mt-5 flex items-end justify-between gap-3">
                <span @class(['text-sm font-medium', 'text-neutral-content/80' => $status === 'wontfix', 'text-base-content/65' => $status !== 'wontfix'])>Dismissed</span>
                <span class="text-3xl font-semibold tracking-[-0.04em]">{{ number_format($dismissedCount) }}</span>
            </span>
        </button>
    </div>

    <section class="overflow-hidden rounded-2xl border border-base-content/10 bg-base-100 shadow-sm">
        <div class="grid gap-3 border-b border-base-content/10 p-4 md:grid-cols-[minmax(16rem,1fr)_12rem_12rem]">
            <x-input icon="o-magnifying-glass" type="search" wire:model.live.debounce.400ms="q" placeholder="Search reports" aria-label="Search reports" />

            <x-select
                icon="o-adjustments-horizontal"
                wire:model.live="status"
                placeholder="Any status"
                placeholder-value=""
                :options="[
                    ['id' => 'new', 'name' => 'New'],
                    ['id' => 'converted', 'name' => 'Converted to task'],
                    ['id' => 'in_progress', 'name' => 'In progress'],
                    ['id' => 'done', 'name' => 'Done'],
                    ['id' => 'wontfix', 'name' => 'Won’t fix'],
                ]"
                aria-label="Filter by status"
            />

            <x-select
                icon="o-folder"
                wire:model.live="source"
                placeholder="Any project"
                placeholder-value=""
                :options="$sources->map(fn ($source) => ['id' => $source, 'name' => str($source)->headline()->toString()])"
                aria-label="Filter by project"
            />
        </div>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-base-content/50">
                        <th>Report</th>
                        <th>Project</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Reporter</th>
                        <th class="text-right">Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        @php
                            $severityClass = match ($report->severity) {
                                'blocker' => 'badge-error',
                                'high' => 'badge-warning',
                                'med' => 'badge-info',
                                default => 'badge-neutral',
                            };
                            $statusClass = match ($report->status) {
                                'done' => 'badge-success',
                                'in_progress' => 'badge-secondary',
                                'wontfix' => 'badge-neutral',
                                default => 'badge-primary',
                            };
                            $typeClass = $report->type === 'bug'
                                ? 'badge-error'
                                : 'badge-secondary';
                        @endphp
                        <tr class="group transition-colors hover:bg-base-content/[0.035]" wire:key="report-{{ $report->id }}">
                            <td class="min-w-72">
                                <a class="font-semibold transition-colors group-hover:text-primary" href="{{ route('reports.show', $report) }}" wire:navigate>
                                    {{ $report->title }}
                                </a>
                                <div class="mt-1 max-w-md truncate text-xs text-base-content/45">{{ $report->description ?: 'No description provided' }}</div>
                            </td>
                            <td><span class="badge badge-neutral uppercase">{{ $report->source_slug }}</span></td>
                            <td><span class="badge {{ $typeClass }} badge-sm capitalize">{{ $report->type }}</span></td>
                            <td><span class="badge {{ $severityClass }} badge-sm capitalize">{{ $report->severity }}</span></td>
                            <td>
                                @if ($report->task)
                                    <a href="{{ route('tasks.show', $report->task) }}" wire:navigate class="badge badge-success badge-sm">Task #{{ $report->task->id }}</a>
                                @else
                                    <span class="badge {{ $statusClass }} badge-sm">{{ str($report->status)->replace('_', ' ')->title() }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="max-w-48 truncate text-sm">{{ $report->reporter_name ?: ($report->reporter_email ?: 'Anonymous reporter') }}</div>
                                @if ($report->reporter_name)
                                    <div class="max-w-48 truncate text-xs text-base-content/45">{{ $report->reporter_email }}</div>
                                @endif
                            </td>
                            <td class="text-right text-sm text-base-content/55" title="{{ $report->created_at }}">{{ $report->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-base-200 text-base-content/40">
                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <path d="M5 5h14v14H5zM8 9h8M8 13h5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="font-semibold">Nothing waiting here</div>
                                <div class="mt-1 text-sm text-base-content/50">Try changing the filters, or enjoy the rare quiet moment.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="border-t border-base-content/10 p-4">
                {{ $reports->links() }}
            </div>
        @endif
    </section>
</div>
