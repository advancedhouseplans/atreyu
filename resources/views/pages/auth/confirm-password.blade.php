<x-layouts::auth :title="__('Confirm password')" :heading="__('Confirm password')" :description="__('This is a secure area. Confirm your identity to continue.')">
    <div class="space-y-6">
        <x-auth-session-status class="alert alert-success text-sm" :status="session('status')" />
        <x-passkey-verify options-route="passkey.confirm-options" submit-route="passkey.confirm" :label="__('Confirm with passkey')" :loading-label="__('Confirming...')" :separator="__('Or confirm with password')" />
        <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
            @csrf
            <x-password label="{{ __('Password') }}" name="password" error-field="password" required autocomplete="current-password" right />
            <x-button class="btn-primary w-full" type="submit" data-test="confirm-password-button" label="{{ __('Confirm') }}" />
        </form>
    </div>
</x-layouts::auth>
