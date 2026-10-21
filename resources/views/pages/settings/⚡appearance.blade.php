<?php

use App\Enums\Accent;
use App\Enums\Appearance;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance settings')] class extends Component {
    public string $appearance = 'system';

    public string $accent = 'iris';

    public function mount(): void
    {
        $this->appearance = auth()->user()->appearance->value;
        $this->accent = auth()->user()->accent->value;
    }

    public function selectAppearance(string $appearance): void
    {
        $selected = Appearance::tryFrom($appearance);

        if (! $selected) {
            $this->addError('appearance', __('That appearance is not available.'));
            $this->appearance = auth()->user()->appearance->value;

            return;
        }

        $this->resetErrorBag('appearance');
        $this->appearance = $selected->value;

        auth()->user()->update(['appearance' => $selected]);

        $this->dispatch('ob-theme-apply', appearance: $this->appearance, accent: $this->accent);
    }

    public function selectAccent(string $accent): void
    {
        $selected = Accent::tryFrom($accent);

        if (! $selected) {
            $this->addError('accent', __('That accent is not available.'));
            $this->accent = auth()->user()->accent->value;

            return;
        }

        $this->resetErrorBag('accent');
        $this->accent = $selected->value;

        auth()->user()->update(['accent' => $selected]);

        $this->dispatch('ob-theme-apply', appearance: $this->appearance, accent: $this->accent);
    }
}; ?>

<section class="w-full p-5 sm:p-7 lg:p-8">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Appearance')">
        <div class="space-y-8">
            <x-ob::section :heading="__('Mode')" :description="__('Saved to your account so Atreyu looks the same on every device.')">
                <x-ob::segmented
                    name="appearance"
                    :label="__('Mode')"
                    :options="collect(Appearance::cases())->map(fn (Appearance $option) => [
                        'value' => $option->value,
                        'label' => __($option->label()),
                        'icon' => match ($option) {
                            Appearance::System => 'computer-desktop',
                            Appearance::Light => 'sun',
                            Appearance::Dark => 'moon',
                        },
                    ])->all()"
                    wire:model="appearance"
                    x-on:change="$store.obTheme.apply({ appearance: $event.target.value }); $wire.selectAppearance($event.target.value)"
                />
                @error('appearance')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </x-ob::section>

            <x-ob::section :heading="__('Accent')" :description="__('Used for buttons, links and highlights.')">
                <div role="radiogroup" aria-label="{{ __('Accent') }}" class="flex flex-wrap gap-3">
                    @foreach (Accent::cases() as $option)
                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                name="accent"
                                value="{{ $option->value }}"
                                aria-label="{{ __(':accent accent', ['accent' => $option->label()]) }}"
                                @checked($accent === $option->value)
                                x-on:change="$store.obTheme.apply({ accent: $event.target.value }); $wire.selectAccent($event.target.value)"
                                class="peer sr-only"
                            >
                            <span
                                class="block size-8 rounded-full border border-hairline transition duration-120 ease-ob peer-checked:ring-2 peer-checked:ring-fg peer-checked:ring-offset-2 peer-checked:ring-offset-surface-1 peer-focus-visible:shadow-[0_0_0_1px_var(--color-accent),0_0_0_5px_var(--color-accent-soft)] max-sm:size-11"
                                style="background-color: {{ $option->swatch() }}"
                            ></span>
                        </label>
                    @endforeach
                </div>
                @error('accent')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </x-ob::section>
        </div>
    </x-pages::settings.layout>
</section>
