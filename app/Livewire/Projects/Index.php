<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showCreate = false;

    public string $name = '';

    public string $description = '';

    public function createProject(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'project';
        $slug = $baseSlug;
        $suffix = 2;

        while (Project::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        Project::create([...$validated, 'slug' => $slug]);

        $this->reset(['name', 'description', 'showCreate']);
    }

    public function render(): View
    {
        return view('livewire.projects.index', [
            'projects' => Project::withCount([
                'tasks' => fn ($query) => $query->whereNull('archived_at'),
            ])->orderBy('name')->get(),
        ]);
    }
}
