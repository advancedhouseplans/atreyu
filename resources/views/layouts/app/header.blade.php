<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->theme?->value ?? 'light' }}">
    <head>@include('partials.head')</head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        <header class="navbar border-b border-base-content/5 bg-base-100/70 px-4 shadow-[0_8px_30px_-22px_rgba(0,0,0,0.45)] backdrop-blur-2xl lg:px-8">
            <div class="flex-1"><x-app-logo :href="route('reports.index')" wire:navigate /></div>
            <nav class="flex-none"><ul class="menu menu-horizontal"><li><a href="{{ route('reports.index') }}" wire:navigate>Reports</a></li></ul></nav>
        </header>
        <main class="mx-auto w-full max-w-[1600px]">{{ $slot }}</main>
    </body>
</html>
