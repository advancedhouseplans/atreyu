<!DOCTYPE html>
@use('App\Enums\Appearance')
@php($user = auth()->user())
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-appearance="{{ $user?->appearance->value ?? 'system' }}" data-mode="{{ ($user?->appearance ?? Appearance::System) === Appearance::Light ? 'light' : 'dark' }}" data-accent="{{ $user?->accent->value ?? 'iris' }}">
    <head>@include('partials.head')</head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        <main class="grid min-h-screen place-items-center p-5">
            <div class="w-full max-w-md">
                <x-app-logo class="mb-8 justify-center" :href="route('home')" wire:navigate />
                <section class="card border border-base-content/5 bg-base-100/75 shadow-[0_24px_80px_-40px_rgba(0,0,0,0.55)] ring-1 ring-base-content/5 backdrop-blur-2xl">
                    <div class="card-body p-7 sm:p-9">{{ $slot }}</div>
                </section>
                <p class="mt-6 text-center text-xs text-base-content/40">Internal workspace · Advanced House Plans</p>
            </div>
        </main>
    </body>
</html>
