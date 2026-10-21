<x-layouts::auth :title="__('Forgot password')" :heading="__('Forgot password?')" :description="__('Enter your email address and we’ll send you a password reset link.')">
    <div class="space-y-6">
        <x-auth-session-status :status="session('status')" />
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <x-ob::input :label="__('Email address')" icon="envelope" name="email" value="{{ old('email') }}" type="email" required autofocus autocomplete="email" placeholder="email@example.com" />
            <x-ob::button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">{{ __('Email password reset link') }}</x-ob::button>
        </form>
    </div>
</x-layouts::auth>
