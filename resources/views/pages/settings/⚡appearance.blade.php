<?php

use App\Enums\Theme;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance settings')] class extends Component {
    public string $theme = Theme::Light->value;

    public function mount(): void
    {
        $this->theme = auth()->user()->theme->value;
    }

    public function selectTheme(string $theme): void
    {
        $selectedTheme = Theme::tryFrom($theme);

        if (! $selectedTheme) {
            $this->addError('theme', __('That theme is not available.'));
            $this->theme = auth()->user()->theme->value;

            return;
        }

        $this->resetErrorBag('theme');
        $this->theme = $selectedTheme->value;

        auth()->user()->update(['theme' => $selectedTheme]);

        $this->dispatch('atreyu-theme-changed', theme: $selectedTheme->value);
    }
}; ?>

<section class="w-full p-5 sm:p-7 lg:p-8">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Appearance')">
        <div class="card rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
            <div class="card-body gap-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="font-semibold">{{ __('Color theme') }}</h3>
                        <p class="mt-1 text-sm text-base-content/60">
                            {{ __('Saved to your account so Atreyu looks the same on every device.') }}
                        </p>
                    </div>
                    <span class="badge badge-primary">{{ Theme::tryFrom($theme)?->label() ?? __('Theme') }}</span>
                </div>

                @foreach ([__('Light themes') => Theme::lightThemes(), __('Dark themes') => Theme::darkThemes()] as $heading => $themes)
                    <section>
                        <div class="mb-3 flex items-center gap-3">
                            <h4 class="text-sm font-semibold">{{ $heading }}</h4>
                            <div class="h-px flex-1 bg-base-content/10"></div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($themes as $option)
                                <label
                                    data-theme="{{ $option->value }}"
                                    class="block cursor-pointer rounded-2xl border bg-base-100/80 p-4 text-base-content shadow-[0_10px_30px_-26px_rgba(0,0,0,0.45)] ring-1 ring-inset ring-base-content/5 transition duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $theme === $option->value ? 'border-primary ring-2 ring-primary/30' : 'border-base-content/10' }}"
                                >
                                    <input
                                        type="radio"
                                        name="atreyu-theme"
                                        value="{{ $option->value }}"
                                        aria-label="{{ $option->label() }}"
                                        class="theme-controller sr-only"
                                        wire:click="selectTheme('{{ $option->value }}')"
                                        @checked($theme === $option->value)
                                    />

                                    <div class="mb-5 flex items-center justify-between gap-2">
                                        <span class="font-semibold">{{ $option->label() }}</span>
                                        @if ($theme === $option->value)
                                            <span class="badge badge-primary badge-sm">{{ __('Selected') }}</span>
                                        @endif
                                    </div>

                                    <div class="mb-4 flex gap-1.5" aria-hidden="true">
                                        <span class="size-5 rounded-full bg-primary"></span>
                                        <span class="size-5 rounded-full bg-secondary"></span>
                                        <span class="size-5 rounded-full bg-accent"></span>
                                        <span class="size-5 rounded-full bg-neutral"></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="btn btn-primary btn-xs pointer-events-none">{{ __('Action') }}</span>
                                        <span class="badge badge-secondary badge-sm">{{ __('Badge') }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </x-pages::settings.layout>
</section>
