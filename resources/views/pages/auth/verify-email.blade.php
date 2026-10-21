<x-layouts::auth :title="__('Email verification')" :heading="__('Verify your email')" :description="__('Click the verification link we sent to your email address.')">
    <div class="space-y-6 text-center">
        @if (session('status') == 'verification-link-sent')
            <x-auth-session-status class="text-left" :status="__('A new verification link has been sent to your email address.')" />
        @endif
        <div class="space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ob::button variant="primary" type="submit" class="w-full">{{ __('Resend verification email') }}</x-ob::button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ob::button variant="ghost" size="sm" type="submit" data-test="logout-button">{{ __('Log out') }}</x-ob::button>
            </form>
        </div>
    </div>
</x-layouts::auth>
