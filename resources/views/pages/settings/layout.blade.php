<div class="grid gap-8 lg:grid-cols-[14rem_minmax(0,1fr)]">
    <nav aria-label="{{ __('Settings') }}">
        <x-menu activate-by-route class="gap-1 rounded-2xl border border-base-content/5 bg-base-100/65 p-2 shadow-[0_14px_40px_-32px_rgba(0,0,0,0.5)] ring-1 ring-base-content/5 backdrop-blur-2xl">
            <x-menu-item title="{{ __('Profile') }}" icon="o-user" :link="route('profile.edit')" exact />
            <x-menu-item title="{{ __('Security') }}" icon="o-shield-check" :link="route('security.edit')" exact />
            <x-menu-item title="{{ __('Appearance') }}" icon="o-swatch" :link="route('appearance.edit')" exact />
        </x-menu>
        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <x-ob::button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle" data-test="settings-logout-button">{{ __('Log out') }}</x-ob::button>
        </form>
    </nav>
    <div class="min-w-0">
        <h2 class="text-lg font-semibold tracking-[-0.015em]">{{ $heading ?? '' }}</h2>
        <div class="mt-5 w-full max-w-2xl">
            {{ $slot }}
        </div>
    </div>
</div>
