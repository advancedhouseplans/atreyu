<x-layouts::auth :title="__('Forgot password')">
    <div class="space-y-6">
        <x-auth-header :title="__('Forgot password?')" :description="__('Enter your email address and we’ll send you a password reset link.')" />
        <x-auth-session-status class="alert alert-success text-sm" :status="session('status')" />
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <x-input label="{{ __('Email address') }}" icon="o-envelope" name="email" value="{{ old('email') }}" type="email" error-field="email" required autofocus autocomplete="email" placeholder="email@example.com" />
            <x-button class="btn-primary w-full" type="submit" data-test="email-password-reset-link-button" label="{{ __('Email password reset link') }}" />
        </form>
    </div>
</x-layouts::auth>
