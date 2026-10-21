<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->theme?->value ?? 'light' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        <x-nav sticky full-width class="border-b border-base-content/5 bg-base-100/70 shadow-[0_8px_30px_-22px_rgba(0,0,0,0.45)] backdrop-blur-2xl lg:hidden">
            <x-slot:brand>
                <label for="atreyu-navigation" class="btn btn-square btn-ghost" aria-label="Open navigation">
                    <x-icon name="o-bars-3" class="size-5" />
                </label>
                <x-app-logo sidebar class="ml-2" :href="route('reports.index')" wire:navigate />
            </x-slot:brand>
        </x-nav>

        <x-main full-width>
            <x-slot:content class="!p-0">
                {{ $slot }}
            </x-slot:content>

            <x-slot:sidebar drawer="atreyu-navigation" collapsible collapse-text="Collapse sidebar" class="border-r border-base-content/5 bg-base-100/65 shadow-[12px_0_36px_-30px_rgba(0,0,0,0.5)] backdrop-blur-2xl">
                <div
                    class="flex min-h-full flex-col py-4"
                    x-bind:class="collapsed ? '[&_.menu>li>a]:justify-center [&_.menu>li>a]:!px-0' : ''"
                >
                    <x-app-logo
                        sidebar
                        class="w-full py-2"
                        x-bind:class="collapsed ? 'justify-center' : 'px-5'"
                        :href="route('reports.index')"
                        wire:navigate
                    />

                    <x-menu activate-by-route class="mt-7 gap-1 px-2 [&>li>a]:rounded-xl [&>li>a]:transition-colors">
                        <x-menu-item title="Reports" icon="o-inbox-stack" :link="route('reports.index')" aria-label="Reports" />
                        <x-menu-item title="Projects" icon="o-folder" :link="route('projects.index')" aria-label="Projects" />
                        <x-menu-item title="Tasks" icon="o-check-circle" :link="route('tasks.index')" aria-label="Tasks" />
                    </x-menu>

                    <div class="mary-hideable mt-auto border-t border-base-content/10 px-3 pt-4">
                        <x-desktop-user-menu />
                    </div>
                    <a
                        href="{{ route('profile.edit') }}"
                        wire:navigate
                        class="display-when-collapsed mx-auto mt-auto hidden rounded-full p-1 hover:bg-base-300"
                        aria-label="Open settings for {{ auth()->user()->name }}"
                    ><x-avatar :placeholder="auth()->user()->initials()" class="!w-9" /></a>
                </div>
            </x-slot:sidebar>
        </x-main>

    </body>
</html>
