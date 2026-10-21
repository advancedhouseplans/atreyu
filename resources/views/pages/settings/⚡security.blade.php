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
        <div class="space-y-6">
            @if (session('success'))<x-auth-session-status :status="session('success')" />@endif

            <x-ob::card>
                <x-ob::section :heading="__('Update password')" :description="__('Use a long, random password to stay secure.')">
                    <form wire:submit="updatePassword" class="space-y-4">
                        @foreach ([['current_password', __('Current password'), 'current-password'], ['password', __('New password'), 'new-password'], ['password_confirmation', __('Confirm password'), 'new-password']] as [$field, $label, $autocomplete])
                            <x-ob::password :label="$label" wire:model="{{ $field }}" required autocomplete="{{ $autocomplete }}" />
                        @endforeach
                        <x-ob::button variant="primary" type="submit" data-test="update-password-button">{{ __('Save password') }}</x-ob::button>
                    </form>
                </x-ob::section>
            </x-ob::card>

            @if ($canManageTwoFactor)
                <x-ob::card wire:cloak>
                    <x-ob::section :heading="__('Two-factor authentication')" :description="__('Add an authenticator app as a second sign-in step.')">
                        <x-slot:actions>
                            <x-ob::badge :color="$twoFactorEnabled ? 'success' : 'neutral'" dot>{{ $twoFactorEnabled ? __('Enabled') : __('Off') }}</x-ob::badge>
                        </x-slot:actions>

                        @if ($twoFactorEnabled)
                            <div class="space-y-5">
                                <p class="text-sm text-fg-2">{{ __('You will be prompted for a secure code from your authenticator during login.') }}</p>
                                <x-ob::button variant="danger" wire:click="disable">{{ __('Disable 2FA') }}</x-ob::button>
                                <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                            </div>
                        @else
                            <div>
                                <x-ob::button variant="primary" wire:click="$dispatch('start-two-factor-setup')">{{ __('Enable 2FA') }}</x-ob::button>
                                <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                            </div>
                        @endif
                    </x-ob::section>
                </x-ob::card>
            @endif

            @if ($canManagePasskeys)
                <x-ob::card wire:cloak>
                    <x-ob::section :heading="__('Passkeys')" :description="__('Use biometrics or a device PIN for passwordless sign-in.')">
                        <div class="divide-y divide-hairline rounded-xl border border-hairline">
                            @forelse ($passkeys as $passkey)
                                <div class="flex items-center justify-between gap-4 p-4" wire:key="passkey-{{ $passkey['id'] }}">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate font-semibold">{{ $passkey['name'] }}</span>
                                            @if ($passkey['authenticator'])<x-ob::badge>{{ $passkey['authenticator'] }}</x-ob::badge>@endif
                                        </div>
                                        <p class="mt-1 text-xs text-fg-3">{{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}@if ($passkey['last_used_at_diff']) · {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}@endif</p>
                                    </div>
                                    <x-ob::button variant="ghost" icon="trash" :label="__('Remove passkey')" wire:click="confirmDelete({{ $passkey['id'] }})" />
                                </div>
                            @empty
                                <div class="p-6 text-center text-sm text-fg-3">{{ __('No passkeys yet') }}</div>
                            @endforelse
                        </div>
                        <x-passkey-registration />
                    </x-ob::section>
                </x-ob::card>
            @endif
        </div>
    </x-pages::settings.layout>

    <x-ob::modal wire:model="showDeleteModal" :title="__('Remove passkey')" size="sm">
        <p class="text-sm text-fg-2">{{ __('Are you sure you want to remove the passkey ":name"?', ['name' => $deletingPasskeyName]) }}</p>

        <x-slot:footer>
            <x-ob::button variant="ghost" wire:click="closeDeleteModal">{{ __('Cancel') }}</x-ob::button>
            <x-ob::button variant="danger" wire:click="deletePasskey">{{ __('Remove passkey') }}</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>
</section>
