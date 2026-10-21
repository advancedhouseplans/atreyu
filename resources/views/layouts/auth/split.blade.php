<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->theme?->value ?? 'light' }}">
    <head>@include('partials.head')</head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        <main class="grid min-h-screen lg:grid-cols-2">
            <section class="relative hidden overflow-hidden bg-primary p-12 text-primary-content shadow-[18px_0_60px_-40px_rgba(0,0,0,0.7)] lg:flex lg:flex-col">
                <x-app-logo class="relative z-10" :href="route('home')" wire:navigate />
                <div class="relative z-10 mt-auto max-w-lg">
                    <div class="mb-5 text-xs font-bold uppercase tracking-[0.24em] opacity-60">The work never ends</div>
                    <h1 class="text-4xl font-bold leading-tight">Keep The Nothing at bay, one task at a time.</h1>
                </div>
                <div class="absolute -bottom-40 -right-32 size-[34rem] rounded-full border-[5rem] border-primary-content/5"></div>
            </section>
            <section class="grid place-items-center p-5 sm:p-10">
                <div class="w-full max-w-md">
                    <x-app-logo class="mb-8 justify-center lg:hidden" :href="route('home')" wire:navigate />
                    <div class="card border border-base-content/5 bg-base-100/75 shadow-[0_24px_80px_-40px_rgba(0,0,0,0.55)] ring-1 ring-base-content/5 backdrop-blur-2xl"><div class="card-body p-7 sm:p-9">{{ $slot }}</div></div>
                </div>
            </section>
        </main>
    </body>
</html>
