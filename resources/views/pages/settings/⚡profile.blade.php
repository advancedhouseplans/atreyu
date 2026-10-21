<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Session::flash('success', __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('reports.index', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full p-5 sm:p-7 lg:p-8">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Profile')">
        @if (session('success'))<div class="alert alert-success mb-5 text-sm">{{ session('success') }}</div>@endif
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <label class="form-control w-full"><span class="label-text mb-2 font-medium">{{ __('Name') }}</span><input class="input input-bordered w-full @error('name') input-error @enderror" wire:model="name" type="text" required autofocus autocomplete="name" />@error('name')<span class="mt-1 text-xs text-error">{{ $message }}</span>@enderror</label>

            <div>
                <label class="form-control w-full"><span class="label-text mb-2 font-medium">{{ __('Email') }}</span><input class="input input-bordered w-full @error('email') input-error @enderror" wire:model="email" type="email" required autocomplete="email" />@error('email')<span class="mt-1 text-xs text-error">{{ $message }}</span>@enderror</label>

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <p class="mt-4 text-sm text-base-content/60">
                            {{ __('Your email address is unverified.') }}

                            <button type="button" class="btn btn-ghost btn-sm mt-2" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-success">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <button class="btn btn-primary" type="submit" data-test="update-profile-button">
                        {{ __('Save') }}
                    </button>
                </div>

            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
