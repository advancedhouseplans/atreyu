<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showModal = false;
    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        app(EnableTwoFactorAuthentication::class)(auth()->user());
        $this->reset('code', 'showVerificationStep');
        $this->resetErrorBag();
        $this->loadSetupData();
        $this->showModal = true;
    }

    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');
            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;
            $this->resetErrorBag();
            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();
        $confirmTwoFactorAuthentication(auth()->user(), $this->code);
        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');
        $this->resetErrorBag();
    }

    public function updatedShowModal(bool $value): void
    {
        if (! $value) {
            $this->closeModal();
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset('code', 'manualSetupKey', 'qrCodeSvg', 'showVerificationStep');
        $this->resetErrorBag();
    }
}; ?>

<div>
    <x-ob::modal
        wire:model.live="showModal"
        :title="$showVerificationStep ? __('Verify authentication code') : __('Enable two-factor authentication')"
        :description="$showVerificationStep ? __('Enter the 6-digit code from your authenticator app.') : __('Scan the QR code or enter the setup key in your authenticator app.')"
    >
        <div x-data="{ copied: false }">
            @if ($showVerificationStep)
                <div class="space-y-5">
                    <x-ob::field error-key="code" for="two-factor-code">
                        <input id="two-factor-code" class="h-11 w-full min-w-0 rounded-lg border border-hairline bg-surface-1 px-3 text-center font-mono text-xl tracking-[0.5em] text-fg placeholder:text-fg-3 focus-ring in-data-invalid:border-danger" wire:model="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" aria-label="{{ __('Authentication code') }}" autofocus />
                    </x-ob::field>
                    <div class="grid grid-cols-2 gap-3">
                        <x-ob::button variant="ghost" wire:click="resetVerification">{{ __('Back') }}</x-ob::button>
                        <x-ob::button variant="primary" wire:click="confirmTwoFactor">{{ __('Confirm') }}</x-ob::button>
                    </div>
                </div>
            @else
                @error('setupData')<div class="mb-4 rounded-lg bg-danger-soft p-3 text-sm text-danger-fg">{{ $message }}</div>@enderror
                @if ($qrCodeSvg)<div class="mx-auto mb-5 aspect-square w-64 max-w-full rounded-xl bg-white p-4">{!! $qrCodeSvg !!}</div>@endif
                @if ($manualSetupKey)
                    <div class="mb-5 flex gap-2">
                        <x-ob::input class="min-w-0 flex-1 font-mono" :aria-label="__('Setup key')" readonly value="{{ $manualSetupKey }}" />
                        <x-ob::button class="self-start" x-on:click="navigator.clipboard.writeText('{{ $manualSetupKey }}'); copied = true; setTimeout(() => copied = false, 1500)">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
                        </x-ob::button>
                    </div>
                @endif
                <x-ob::button variant="primary" class="w-full" wire:click="showVerificationIfNecessary" :disabled="$errors->has('setupData')">{{ __('Continue') }}</x-ob::button>
            @endif
        </div>
    </x-ob::modal>
</div>
