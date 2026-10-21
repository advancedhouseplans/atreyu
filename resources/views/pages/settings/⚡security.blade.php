<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $canManageTwoFactor;
    public bool $twoFactorEnabled;
    public bool $requiresConfirmation;

    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $exception) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $exception;
        }

        Auth::user()->update(['password' => $validated['password']]);
        $this->reset('current_password', 'password', 'password_confirmation');
        Session::flash('success', __('Password updated.'));
    }

    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])->toArray();
    }

    public function confirmDelete(int $passkeyId): void
    {
        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);
        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);
        $deletePasskey(auth()->user(), $passkey);
        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());
        $this->twoFactorEnabled = false;
    }
}; ?>

<section class="w-full p-5 sm:p-7 lg:p-8">
    @include('partials.settings-heading')
    <x-pages::settings.layout :heading="__('Security')">
        @if (session('success'))<div class="alert alert-success mb-5 text-sm">{{ session('success') }}</div>@endif

        <section class="card rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl">
            <div class="card-body">
                <h3 class="card-title text-lg">{{ __('Update password') }}</h3>
                <p class="text-sm text-base-content/55">{{ __('Use a long, random password to stay secure.') }}</p>
                <form wire:submit="updatePassword" class="mt-4 space-y-4">
                    @foreach ([['current_password', __('Current password'), 'current-password'], ['password', __('New password'), 'new-password'], ['password_confirmation', __('Confirm password'), 'new-password']] as [$field, $label, $autocomplete])
                        <label class="form-control w-full"><span class="label-text mb-2 font-medium">{{ $label }}</span><input class="input input-bordered w-full @error($field) input-error @enderror" wire:model="{{ $field }}" type="password" required autocomplete="{{ $autocomplete }}" />@error($field)<span class="mt-1 text-xs text-error">{{ $message }}</span>@enderror</label>
                    @endforeach
                    <button class="btn btn-primary" type="submit" data-test="update-password-button">{{ __('Save password') }}</button>
                </form>
            </div>
        </section>

        @if ($canManageTwoFactor)
            <section class="card mt-6 rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl" wire:cloak>
                <div class="card-body">
                    <div class="flex items-center justify-between gap-4"><div><h3 class="card-title text-lg">{{ __('Two-factor authentication') }}</h3><p class="mt-1 text-sm text-base-content/55">{{ __('Add an authenticator app as a second sign-in step.') }}</p></div><span class="badge {{ $twoFactorEnabled ? 'badge-success' : 'badge-ghost' }}">{{ $twoFactorEnabled ? __('Enabled') : __('Off') }}</span></div>
                    @if ($twoFactorEnabled)
                        <div class="mt-4 space-y-5"><p class="text-sm text-base-content/70">{{ __('You will be prompted for a secure code from your authenticator during login.') }}</p><button type="button" class="btn btn-error" wire:click="disable">{{ __('Disable 2FA') }}</button><livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation /></div>
                    @else
                        <div class="mt-4"><button type="button" class="btn btn-primary" wire:click="$dispatch('start-two-factor-setup')">{{ __('Enable 2FA') }}</button><livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" /></div>
                    @endif
                </div>
            </section>
        @endif

        @if ($canManagePasskeys)
            <section class="card mt-6 rounded-3xl border border-base-content/5 bg-base-100/80 shadow-[0_18px_50px_-36px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-xl" wire:cloak>
                <div class="card-body">
                    <h3 class="card-title text-lg">{{ __('Passkeys') }}</h3><p class="text-sm text-base-content/55">{{ __('Use biometrics or a device PIN for passwordless sign-in.') }}</p>
                    <div class="mt-4 divide-y divide-base-content/10 rounded-box border border-base-content/10">
                        @forelse ($passkeys as $passkey)
                            <div class="flex items-center justify-between gap-4 p-4"><div class="min-w-0"><div class="flex items-center gap-2"><span class="truncate font-semibold">{{ $passkey['name'] }}</span>@if ($passkey['authenticator'])<span class="badge badge-ghost badge-sm">{{ $passkey['authenticator'] }}</span>@endif</div><p class="mt-1 text-xs text-base-content/45">{{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}@if ($passkey['last_used_at_diff']) · {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}@endif</p></div><button type="button" class="btn btn-square btn-ghost btn-sm text-error" wire:click="confirmDelete({{ $passkey['id'] }})" aria-label="{{ __('Remove passkey') }}">×</button></div>
                        @empty
                            <div class="p-6 text-center text-sm text-base-content/50">{{ __('No passkeys yet') }}</div>
                        @endforelse
                    </div>
                    <div class="mt-4"><x-passkey-registration /></div>
                </div>
            </section>
        @endif
    </x-pages::settings.layout>

    @if ($showDeleteModal)
        <div class="modal modal-open" role="dialog"><div class="modal-box"><h3 class="text-lg font-bold">{{ __('Remove passkey') }}</h3><p class="py-4 text-sm text-base-content/65">{{ __('Are you sure you want to remove the passkey ":name"?', ['name' => $deletingPasskeyName]) }}</p><div class="modal-action"><button type="button" class="btn btn-ghost" wire:click="closeDeleteModal">{{ __('Cancel') }}</button><button type="button" class="btn btn-error" wire:click="deletePasskey">{{ __('Remove passkey') }}</button></div></div><button type="button" class="modal-backdrop" wire:click="closeDeleteModal">Close</button></div>
    @endif
</section>
