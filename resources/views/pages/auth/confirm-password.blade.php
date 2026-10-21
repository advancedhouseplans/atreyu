<x-layouts::auth :title="__('Confirm password')" :heading="__('Confirm password')" :description="__('This is a secure area. Confirm your identity to continue.')">
    <div class="space-y-6">
        <x-auth-session-status :status="session('status')" />
        <x-passkey-verify options-route="passkey.confirm-options" submit-route="passkey.confirm" :label="__('Confirm with passkey')" :loading-label="__('Confirming...')" :separator="__('Or confirm with password')" />
        <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
            @csrf
            <x-ob::password :label="__('Password')" name="password" required autocomplete="current-password" />
            <x-ob::button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">{{ __('Confirm') }}</x-ob::button>
        </form>
    </div>
</x-layouts::auth>
