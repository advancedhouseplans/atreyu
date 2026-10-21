<?php

namespace App\Livewire\Projects;

use App\Actions\CreateReporterInstallation;
use App\Models\Project;
use App\Models\ReporterInstallation;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Project $project;

    public bool $showCreate = false;

    public bool $showCredential = false;

    public string $name = '';

    public string $environment = 'local';

    public string $issuedCredential = '';

    public string $issuedName = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function createInstallation(CreateReporterInstallation $creator): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'environment' => ['required', Rule::in(['local', 'staging', 'production'])],
        ]);

        /** @var User $actor */
        $actor = auth()->user();
        $result = $creator->handle($this->project, $actor, $validated['name'], $validated['environment']);

        $this->showCreate = false;
        $this->issuedCredential = $result['credential'];
        $this->issuedName = $result['installation']->name;
        $this->showCredential = true;
        $this->reset(['name']);
    }

    public function rotateInstallation(int $installationId, CreateReporterInstallation $creator): void
    {
        $installation = $this->installation($installationId);

        $this->issuedCredential = $creator->rotate($installation);
        $this->issuedName = $installation->name;
        $this->showCredential = true;
    }

    public function revokeInstallation(int $installationId): void
    {
        $this->installation($installationId)->update(['revoked_at' => now()]);
    }

    public function clearCredential(): void
    {
        $this->issuedCredential = '';
        $this->issuedName = '';
        $this->showCredential = false;
    }

    public function render(): View
    {
        return view('livewire.projects.show', [
            'installations' => $this->project->reporterInstallations()
                ->withCount('reports')
                ->latest()
                ->get(),
        ]);
    }

    private function installation(int $installationId): ReporterInstallation
    {
        return $this->project->reporterInstallations()->findOrFail($installationId);
    }
}
