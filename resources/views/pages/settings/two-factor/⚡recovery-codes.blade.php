<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div
    class="space-y-6 rounded-xl border border-hairline bg-surface-2 p-5"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="space-y-2">
        <h4 class="font-semibold">{{ __('2FA recovery codes') }}</h4>
        <p class="text-sm text-fg-2">
            {{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
        </p>
    </div>

    <div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-ob::button variant="primary" size="sm"
                x-show="!showRecoveryCodes"
                x-on:click="showRecoveryCodes = true"
                aria-expanded="false"
                aria-controls="recovery-codes-section"
            >
                {{ __('View recovery codes') }}
            </x-ob::button>

            <x-ob::button variant="primary" size="sm"
                x-show="showRecoveryCodes"
                x-on:click="showRecoveryCodes = false"
                aria-expanded="true"
                aria-controls="recovery-codes-section"
            >
                {{ __('Hide recovery codes') }}
            </x-ob::button>

            @if (filled($recoveryCodes))
                <x-ob::button size="sm"
                    x-show="showRecoveryCodes"
                    wire:click="regenerateRecoveryCodes"
                >
                    {{ __('Regenerate codes') }}
                </x-ob::button>
            @endif
        </div>

        <div
            x-show="showRecoveryCodes"
            x-transition
            id="recovery-codes-section"
            class="relative overflow-hidden"
            x-bind:aria-hidden="!showRecoveryCodes"
        >
            <div class="mt-3 space-y-3">
                @error('recoveryCodes')
                    <div class="rounded-lg bg-danger-soft p-3 text-sm text-danger-fg">{{ $message }}</div>
                @enderror

                @if (filled($recoveryCodes))
                    <div
                        class="grid gap-1 rounded-lg bg-surface-3 p-4 font-mono text-sm"
                        role="list"
                        aria-label="{{ __('Recovery codes') }}"
                    >
                        @foreach($recoveryCodes as $code)
                            <div
                                role="listitem"
                                class="select-text"
                                wire:loading.class="opacity-50 animate-pulse"
                            >
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-fg-3">
                        {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
