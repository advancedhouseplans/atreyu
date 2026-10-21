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

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset('code', 'manualSetupKey', 'qrCodeSvg', 'showVerificationStep');
        $this->resetErrorBag();
    }
}; ?>

<div>
    @if ($showModal)
        <div class="modal modal-open" role="dialog">
        <div class="modal-box max-w-md" x-data="{ copied: false }">
            <button type="button" class="btn btn-circle btn-ghost btn-sm absolute right-3 top-3" wire:click="closeModal" aria-label="Close">✕</button>
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 grid size-12 place-items-center rounded-full bg-primary/10 text-primary"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM15 14h2v2h-2zM19 14h1v5h-5v1M14 18h2" /></svg></div>
                <h3 class="text-xl font-bold">{{ $showVerificationStep ? __('Verify authentication code') : __('Enable two-factor authentication') }}</h3>
                <p class="mt-2 text-sm text-base-content/55">{{ $showVerificationStep ? __('Enter the 6-digit code from your authenticator app.') : __('Scan the QR code or enter the setup key in your authenticator app.') }}</p>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-5"><input class="input input-bordered w-full text-center font-mono text-xl tracking-[0.5em] @error('code') input-error @enderror" wire:model="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus />@error('code')<p class="text-sm text-error">{{ $message }}</p>@enderror<div class="grid grid-cols-2 gap-3"><button type="button" class="btn btn-ghost" wire:click="resetVerification">{{ __('Back') }}</button><button type="button" class="btn btn-primary" wire:click="confirmTwoFactor">{{ __('Confirm') }}</button></div></div>
            @else
                @error('setupData')<div class="alert alert-error mb-4 text-sm">{{ $message }}</div>@enderror
                @if ($qrCodeSvg)<div class="mx-auto mb-5 aspect-square w-64 rounded-box bg-white p-4">{!! $qrCodeSvg !!}</div>@endif
                @if ($manualSetupKey)
                    <div class="join mb-5 flex"><input class="input join-item input-bordered min-w-0 flex-1 font-mono" readonly value="{{ $manualSetupKey }}" /><button type="button" class="btn btn-secondary join-item" @click="navigator.clipboard.writeText('{{ $manualSetupKey }}'); copied = true; setTimeout(() => copied = false, 1500)"><span x-show="!copied">{{ __('Copy') }}</span><span x-show="copied" x-cloak>{{ __('Copied') }}</span></button></div>
                @endif
                <button type="button" class="btn btn-primary w-full" wire:click="showVerificationIfNecessary" @disabled($errors->has('setupData'))>{{ __('Continue') }}</button>
            @endif
        </div>
        <button type="button" class="modal-backdrop" wire:click="closeModal">Close</button>
        </div>
    @endif
</div>
