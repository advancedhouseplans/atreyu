<x-layouts::auth :title="__('Reset password')" :heading="__('Reset password')" :description="__('Choose a new password for your account.')">
    <div class="space-y-6">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">
            <x-input label="{{ __('Email') }}" icon="o-envelope" name="email" value="{{ request('email') }}" type="email" error-field="email" required autocomplete="email" />
            <x-password label="{{ __('Password') }}" name="password" error-field="password" required autocomplete="new-password" right />
            <x-password label="{{ __('Confirm password') }}" name="password_confirmation" required autocomplete="new-password" right />
            <x-button class="btn-primary w-full" type="submit" data-test="reset-password-button" label="{{ __('Reset password') }}" />
        </form>
    </div>
</x-layouts::auth>
