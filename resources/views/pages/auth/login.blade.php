<x-layouts::auth :title="__('Log in')" :heading="__('Welcome back')" :description="__('Sign in to continue to Atreyu')">
    <div class="space-y-6">
        <x-auth-session-status class="alert alert-success text-sm" :status="session('status')" />
        <x-passkey-verify />
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <x-input label="{{ __('Email address') }}" icon="o-envelope" name="email" value="{{ old('email') }}" type="email" error-field="email" required autofocus autocomplete="email" placeholder="email@example.com" />
            <div>
                <x-password label="{{ __('Password') }}" name="password" error-field="password" required autocomplete="current-password" placeholder="{{ __('Password') }}" right />
                @if (Route::has('password.request'))
                    <div class="mt-2 text-right"><a class="link link-primary text-xs" href="{{ route('password.request') }}" wire:navigate>{{ __('Forgot password?') }}</a></div>
                @endif
            </div>
            <label class="label cursor-pointer justify-start gap-3"><input class="checkbox checkbox-primary checkbox-sm" type="checkbox" name="remember" @checked(old('remember')) /><span class="label-text">{{ __('Remember me') }}</span></label>
            <x-button class="btn-primary w-full" type="submit" data-test="login-button" label="{{ __('Log in') }}" />
        </form>
        @if (Route::has('register'))<p class="text-center text-sm text-base-content/60">{{ __('Don\'t have an account?') }} <a class="link link-primary" href="{{ route('register') }}" wire:navigate>{{ __('Sign up') }}</a></p>@endif
    </div>
</x-layouts::auth>
