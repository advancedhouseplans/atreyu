@props(['title' => null, 'heading' => null, 'description' => null])

<!DOCTYPE html>
<html @include('partials.html-attributes')>
    <head>@include('partials.head')</head>
    <body>
        <main class="grid min-h-dvh place-items-center bg-canvas p-5 text-fg">
            <div class="w-full max-w-sm">
                <x-app-logo class="mb-8 justify-center" :href="route('home')" wire:navigate />
                <x-ob::card class="p-7 sm:p-8">
                    @if ($heading)
                        <div class="mb-6 text-center">
                            <h1 class="text-2xl font-semibold tracking-[-0.02em]">{{ $heading }}</h1>
                            @if ($description)
                                <p class="mt-2 text-sm text-fg-2">{{ $description }}</p>
                            @endif
                        </div>
                    @endif
                    {{ $slot }}
                </x-ob::card>
                <p class="mt-6 text-center text-xs text-fg-3">Internal workspace · Advanced House Plans</p>
            </div>
        </main>
        <x-ob::toasts />
    </body>
</html>
