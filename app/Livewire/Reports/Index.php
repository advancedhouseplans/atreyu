<?php

namespace App\Livewire\Reports;

use App\Models\Report;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $source = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $severity = '';

    #[Url]
    public string $view = 'inbox';

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDir = 'desc';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingView(): void
    {
        $this->resetPage();
    }

    public function updatingSource(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingSeverity(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function updatingSortDir(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $allowedSorts = ['created_at', 'severity', 'title'];
        $sortBy = in_array($this->sortBy, $allowedSorts, true) ? $this->sortBy : 'created_at';
        $sortDir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        $query = Report::query()
            ->whereNull('occurrence_parent_id')
            ->when($this->q, fn ($x) => $x->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->q}%")
                ->orWhere('description', 'like', "%{$this->q}%")))
            ->when($this->source, fn ($x) => $x->where('source_slug', $this->source))
            ->when($this->type, fn ($x) => $x->where('type', $this->type))
            ->when($this->severity, fn ($x) => $x->where('severity', $this->severity))
            ->when($this->view === 'inbox', fn ($x) => $x->whereNull('archived_at')->whereDoesntHave('task'))
            ->when($this->view === 'triaged', fn ($x) => $x->whereNull('archived_at')->whereHas('task'))
            ->when($this->view === 'archived', fn ($x) => $x->whereNotNull('archived_at'))
            ->when(! in_array($this->view, ['inbox', 'triaged', 'archived', 'all'], true), fn ($x) => $x->whereNull('archived_at')->whereDoesntHave('task'))
            ->orderBy($sortBy === 'created_at' ? 'updated_at' : $sortBy, $sortDir);

        $activity = $this->reportActivity();

        return view('livewire.reports.index', [
            'reports' => $query->with('task')->withCount('occurrences')->withMax('occurrences', 'created_at')->paginate(25),
            'sources' => Report::query()->select('source_slug')->distinct()->orderBy('source_slug')->pluck('source_slug'),
            'inboxCount' => Report::whereNull('occurrence_parent_id')->whereNull('archived_at')->whereDoesntHave('task')->count(),
            'triagedCount' => Report::whereNull('occurrence_parent_id')->whereNull('archived_at')->whereHas('task')->count(),
            'archivedCount' => Report::whereNull('occurrence_parent_id')->whereNotNull('archived_at')->count(),
            'activityDays' => $activity['days'],
            'activityTotal' => $activity['total'],
            'activityMonths' => $activity['months'],
            'activityYear' => $activity['year'],
        ]);
    }

    /**
     * @return array{
     *     days: list<array{date: string, label: string, count: int, level: int}>,
     *     total: int,
     *     months: list<array{label: string, column: int}>,
     *     year: int
     * }
     */
    private function reportActivity(): array
    {
        $start = today()->startOfYear();
        $end = today()->endOfYear();
        $counts = Report::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as report_date, COUNT(*) as report_count')
            ->groupBy('report_date')
            ->pluck('report_count', 'report_date');

        $days = array_values(collect(range(0, $start->daysInYear - 1))->map(function (int $offset) use ($start, $counts): array {
            $date = $start->copy()->addDays($offset);
            $count = (int) ($counts[$date->toDateString()] ?? 0);

            return [
                'date' => $date->toDateString(),
                'label' => $date->format('M j, Y'),
                'count' => $count,
                'level' => match (true) {
                    $count === 0 => 0,
                    $count === 1 => 1,
                    $count <= 3 => 2,
                    $count <= 7 => 3,
                    default => 4,
                },
            ];
        })->all());

        $months = array_values(collect($days)
            ->filter(fn (array $day): bool => Carbon::parse($day['date'])->day === 1)
            ->map(function (array $day) use ($start): array {
                $date = Carbon::parse($day['date']);

                return [
                    'label' => $date->format('M'),
                    'column' => intdiv((int) $start->diffInDays($date), 7) + 1,
                ];
            })
            ->values()
            ->all());

        return [
            'days' => $days,
            'total' => array_sum(array_column($days, 'count')),
            'months' => $months,
            'year' => $start->year,
        ];
    }
}
