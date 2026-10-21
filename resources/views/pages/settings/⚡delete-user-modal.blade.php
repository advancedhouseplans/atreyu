<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate(['password' => $this->currentPasswordRules()]);
        tap(Auth::user(), $logout(...))->delete();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <input id="confirm-user-deletion" type="checkbox" class="modal-toggle" @checked($errors->isNotEmpty()) />
    <div class="modal" role="dialog">
        <form wire:submit="deleteUser" class="modal-box space-y-6">
            <div><h3 class="text-lg font-bold">{{ __('Are you sure you want to delete your account?') }}</h3><p class="mt-2 text-sm text-base-content/60">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Enter your password to confirm.') }}</p></div>
            <label class="form-control w-full"><span class="label-text mb-2 font-medium">{{ __('Password') }}</span><input class="input input-bordered w-full @error('password') input-error @enderror" wire:model="password" type="password" />@error('password')<span class="mt-1 text-xs text-error">{{ $message }}</span>@enderror</label>
            <div class="modal-action"><label for="confirm-user-deletion" class="btn btn-ghost">{{ __('Cancel') }}</label><button class="btn btn-error" type="submit" data-test="confirm-delete-user-button">{{ __('Delete account') }}</button></div>
        </form>
        <label class="modal-backdrop" for="confirm-user-deletion">Close</label>
    </div>
</div>
