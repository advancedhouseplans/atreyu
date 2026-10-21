<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Global search for the app shell. It is only mounted inside the authenticated
 * layout (route-group `auth` middleware) and every user may see every record,
 * so the only scoping is archived-item exclusion.
 */
class CommandPalette extends Component
{
    private const RESULT_LIMIT = 5;

    public string $query = '';

    public function render(): View
    {
        $search = trim($this->query);

        if (mb_strlen($search) < 2) {
            return view('livewire.command-palette', [
                'reports' => collect(),
                'tasks' => collect(),
                'projects' => collect(),
            ]);
        }

        return view('livewire.command-palette', [
            'reports' => $this->reports($search),
            'tasks' => $this->tasks($search),
            'projects' => $this->projects($search),
        ]);
    }

    /** @return Collection<int, Report> */
    private function reports(string $search): Collection
    {
        return Report::query()
            ->whereNull('occurrence_parent_id')
            ->whereNull('archived_at')
            ->where('title', 'like', "%{$search}%")
            ->latest()
            ->limit(self::RESULT_LIMIT)
            ->get();
    }

    /** @return Collection<int, Task> */
    private function tasks(string $search): Collection
    {
        $numericId = preg_match('/(?:^|-)\s*(\d+)$/', $search, $matches) ? (int) $matches[1] : null;

        return Task::query()
            ->whereNull('archived_at')
            ->where(function ($query) use ($search, $numericId): void {
                $query->where('title', 'like', "%{$search}%")
                    ->when($numericId, fn ($query) => $query->orWhereKey($numericId));
            })
            ->latest()
            ->limit(self::RESULT_LIMIT)
            ->get();
    }

    /** @return Collection<int, Project> */
    private function projects(string $search): Collection
    {
        return Project::query()
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(self::RESULT_LIMIT)
            ->get();
    }
}
