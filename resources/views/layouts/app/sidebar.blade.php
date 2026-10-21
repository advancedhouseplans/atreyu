<!DOCTYPE html>
<html @include('partials.html-attributes')>
    <head>
        @include('partials.head')
    </head>
    <body>
        <x-ob::app-shell>
            <x-slot:sidebar>
                <x-ob::sidebar>
                    <x-slot:brand>
                        <x-app-logo sidebar :href="route('reports.index')" wire:navigate />
                    </x-slot:brand>

                    <button
                        type="button"
                        aria-label="Search"
                        x-on:click="$dispatch('command-palette-open')"
                        class="flex h-9 w-full items-center gap-3 rounded-lg px-3.5 text-sm text-fg-3 transition duration-120 ease-ob hover:bg-surface-3 hover:text-fg focus-ring"
                    >
                        <x-ob::icon name="magnifying-glass" class="size-5" />
                        <span x-show="!collapsed" class="flex-1 truncate text-left">
                            Search
                        </span>
                        <x-ob::kbd x-show="!collapsed">⌘K</x-ob::kbd>
                    </button>

                    <x-ob::sidebar.item :href="route('reports.index')" icon="inbox-stack" label="Reports" />
                    <x-ob::sidebar.item :href="route('projects.index')" icon="folder" label="Projects" />
                    <x-ob::sidebar.item :href="route('tasks.index')" icon="check-circle" label="Tasks" />

                    <x-slot:footer>
                        <x-ob::dropdown position="top-start" class="w-full">
                            <x-slot:trigger>
                                <button
                                    type="button"
                                    aria-label="{{ auth()->user()->name }}"
                                    data-test="sidebar-menu-button"
                                    class="flex h-12 w-full items-center gap-3 rounded-lg px-2 text-left transition duration-120 ease-ob hover:bg-surface-3 focus-ring"
                                >
                                    <x-ob::avatar :initials="auth()->user()->initials()" />
                                    <span x-show="!collapsed" class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-fg">{{ auth()->user()->name }}</span>
                                        <span class="block truncate text-xs text-fg-3">{{ auth()->user()->email }}</span>
                                    </span>
                                </button>
                            </x-slot:trigger>

                            <x-ob::menu.item icon="cog-6-tooth" :href="route('profile.edit')" navigate>Settings</x-ob::menu.item>
                            <x-ob::menu.separator />
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-ob::menu.item type="submit" icon="arrow-right-start-on-rectangle" data-test="logout-button">Log out</x-ob::menu.item>
                            </form>
                        </x-ob::dropdown>
                    </x-slot:footer>
                </x-ob::sidebar>
            </x-slot:sidebar>

            <x-slot:dock>
                <x-ob::dock>
                    <x-ob::dock.item :href="route('reports.index')" icon="inbox-stack" label="Reports" />
                    <x-ob::dock.item :href="route('projects.index')" icon="folder" label="Projects" />
                    <x-ob::dock.action icon="plus" label="New task" x-on:click="$dispatch('command-palette-open')" />
                    <x-ob::dock.item :href="route('tasks.index')" icon="check-circle" label="Tasks" />
                    {{-- dock.item renders an icon only, so the avatar link reuses its classes by hand. --}}
                    <a
                        href="{{ route('profile.edit') }}"
                        wire:navigate
                        aria-label="Settings"
                        @if (request()->is('settings', 'settings/*')) aria-current="page" @endif
                        class="flex size-11 items-center justify-center rounded-[14px] transition duration-120 ease-ob hover:bg-surface-3 focus-ring {{ request()->is('settings', 'settings/*') ? 'bg-accent-soft' : '' }}"
                    >
                        <x-ob::avatar :initials="auth()->user()->initials()" size="sm" />
                    </a>
                </x-ob::dock>
            </x-slot:dock>

            {{ $slot }}
        </x-ob::app-shell>

        <x-ob::toasts />
        <livewire:command-palette />
    </body>
</html>
