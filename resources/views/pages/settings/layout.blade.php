@php
    $links = [
        ['label' => __('Profile'), 'icon' => 'user', 'route' => 'profile.edit'],
        ['label' => __('Security'), 'icon' => 'shield-check', 'route' => 'security.edit'],
        ['label' => __('Appearance'), 'icon' => 'swatch', 'route' => 'appearance.edit'],
    ];
@endphp

<div class="grid gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8">
    <nav aria-label="{{ __('Settings') }}" class="space-y-4">
        <ul class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible">
            @foreach ($links as $link)
                @php($active = request()->routeIs($link['route']))
                <li class="lg:w-full">
                    <a
                        href="{{ route($link['route']) }}"
                        wire:navigate
                        @if ($active) aria-current="page" @endif
                        @class([
                            'flex h-8 items-center gap-2 whitespace-nowrap rounded-full px-3 text-sm font-medium transition duration-120 ease-ob focus-ring max-sm:h-11 lg:h-9 lg:w-full lg:gap-3 lg:rounded-lg lg:px-3.5',
                            'bg-surface-3 text-fg' => $active,
                            'text-fg-2 hover:bg-surface-3 hover:text-fg' => ! $active,
                        ])
                    >
                        <x-ob::icon :name="$link['icon']" :class="'size-4 lg:size-5 '.($active ? 'text-accent-fg' : 'text-fg-3')" />
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('logout') }}">
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
