<x-layouts::auth :title="__('Create account')" :heading="__('Create an account')" :description="__('Join the Atreyu workspace.')">
    <div class="space-y-6">
        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf
            <x-ob::input :label="__('Name')" icon="user" name="name" value="{{ old('name') }}" required autocomplete="name" :placeholder="__('Full name')" />
            <x-ob::input :label="__('Email address')" icon="envelope" name="email" value="{{ old('email') }}" type="email" required autocomplete="email" placeholder="email@example.com" />
            <x-ob::password :label="__('Password')" name="password" required autocomplete="new-password" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" />
            <x-ob::password :label="__('Confirm password')" name="password_confirmation" required autocomplete="new-password" />
            <x-ob::button variant="primary" type="submit" class="w-full" data-test="register-user-button">{{ __('Create account') }}</x-ob::button>
        </form>
        <p class="text-center text-sm text-fg-2">{{ __('Already have an account?') }} <a class="rounded text-accent-fg hover:underline focus-ring" href="{{ route('login') }}" wire:navigate>{{ __('Log in') }}</a></p>
    </div>
</x-layouts::auth>
