<x-layouts::auth :title="__('Log in')" :heading="__('Welcome back')" :description="__('Sign in to continue to Atreyu')">
    <div class="space-y-6">
        <x-auth-session-status :status="session('status')" />
        <x-passkey-verify />
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <x-ob::input :label="__('Email address')" icon="envelope" name="email" value="{{ old('email') }}" type="email" required autofocus autocomplete="email" placeholder="email@example.com" />
            <div>
                <x-ob::password :label="__('Password')" name="password" required autocomplete="current-password" :placeholder="__('Password')" />
                @if (Route::has('password.request'))
                    <div class="mt-2 text-right"><a class="rounded text-xs text-accent-fg hover:underline focus-ring" href="{{ route('password.request') }}" wire:navigate>{{ __('Forgot password?') }}</a></div>
                @endif
            </div>
            <x-ob::checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />
            <x-ob::button variant="primary" type="submit" class="w-full" data-test="login-button">{{ __('Log in') }}</x-ob::button>
        </form>
        @if (Route::has('register'))<p class="text-center text-sm text-fg-2">{{ __('Don\'t have an account?') }} <a class="rounded text-accent-fg hover:underline focus-ring" href="{{ route('register') }}" wire:navigate>{{ __('Sign up') }}</a></p>@endif
    </div>
</x-layouts::auth>
