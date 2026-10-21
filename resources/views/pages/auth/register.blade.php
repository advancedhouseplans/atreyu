<x-layouts::auth :title="__('Create account')" :heading="__('Create an account')" :description="__('Join the Atreyu workspace.')">
    <div class="space-y-6">
        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf
            <x-input label="{{ __('Name') }}" icon="o-user" name="name" value="{{ old('name') }}" error-field="name" required autocomplete="name" placeholder="{{ __('Full name') }}" />
            <x-input label="{{ __('Email address') }}" icon="o-envelope" name="email" value="{{ old('email') }}" type="email" error-field="email" required autocomplete="email" placeholder="email@example.com" />
            <x-password label="{{ __('Password') }}" name="password" error-field="password" required autocomplete="new-password" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" right />
            <x-password label="{{ __('Confirm password') }}" name="password_confirmation" required autocomplete="new-password" right />
            <x-button class="btn-primary w-full" type="submit" data-test="register-user-button" label="{{ __('Create account') }}" />
        </form>
        <p class="text-center text-sm text-base-content/60">{{ __('Already have an account?') }} <a class="link link-primary" href="{{ route('login') }}" wire:navigate>{{ __('Log in') }}</a></p>
    </div>
</x-layouts::auth>
