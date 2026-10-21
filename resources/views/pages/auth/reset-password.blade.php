<x-layouts::auth :title="__('Reset password')" :heading="__('Reset password')" :description="__('Choose a new password for your account.')">
    <div class="space-y-6">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">
            <x-ob::input :label="__('Email')" icon="envelope" name="email" value="{{ request('email') }}" type="email" required autocomplete="email" />
            <x-ob::password :label="__('Password')" name="password" required autocomplete="new-password" />
            <x-ob::password :label="__('Confirm password')" name="password_confirmation" required autocomplete="new-password" />
            <x-ob::button variant="primary" type="submit" class="w-full" data-test="reset-password-button">{{ __('Reset password') }}</x-ob::button>
        </form>
    </div>
</x-layouts::auth>
