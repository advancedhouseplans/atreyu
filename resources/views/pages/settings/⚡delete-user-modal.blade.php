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
    <x-ob::modal name="delete-account" :title="__('Are you sure you want to delete your account?')" :description="__('Once your account is deleted, all of its resources and data will be permanently deleted. Enter your password to confirm.')">
        <form id="delete-account-form" wire:submit="deleteUser">
            <x-ob::password :label="__('Password')" wire:model="password" />
        </form>

        <x-slot:footer>
            <x-ob::button variant="ghost" x-on:click="$dispatch('modal-close', { name: 'delete-account' })">{{ __('Cancel') }}</x-ob::button>
            <x-ob::button variant="danger" type="submit" form="delete-account-form" data-test="confirm-delete-user-button">{{ __('Delete account') }}</x-ob::button>
        </x-slot:footer>
    </x-ob::modal>
</div>
