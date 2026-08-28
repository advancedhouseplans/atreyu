<?php

namespace App\Livewire\Reports;

use App\Models\Report;
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
    public string $status = '';

    #[Url]
    public string $assignee = '';

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDir = 'desc';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
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

    public function updatingAssignee(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Report::query()
            ->when($this->q, fn ($x) => $x->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->q}%")
                ->orWhere('description', 'like', "%{$this->q}%")))
            ->when($this->source, fn ($x) => $x->where('source_slug', $this->source))
            ->when($this->type, fn ($x) => $x->where('type', $this->type))
            ->when($this->severity, fn ($x) => $x->where('severity', $this->severity))
            ->when($this->status, fn ($x) => $x->where('status', $this->status))
            ->when(
                $this->assignee === 'mine' && auth()->id(),
                fn ($x) => $x->where('assignee_id', auth()->id()),
                fn ($x) => $x->when($this->assignee, fn ($y) => $y->where('assignee_id', $this->assignee))
            )
            ->orderBy($this->sortBy, $this->sortDir);

        return view('livewire.reports.index', ['reports' => $query->paginate(25)]);
    }
}
