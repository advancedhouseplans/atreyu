@props([
    'sidebar' => false,
])

<a {{ $attributes->class(['flex items-center gap-3']) }}>
    <x-app-logo-icon @class(['shrink-0 text-black dark:text-white', 'size-8' => $sidebar, 'size-10' => ! $sidebar]) />
    <span class="leading-none">
        <span class="block text-lg font-semibold tracking-tight text-fg">Atreyu</span>
        @unless ($sidebar)
            <span class="mt-1 block text-[0.65rem] font-medium uppercase tracking-[0.22em] text-fg-3">Advanced House Plans</span>
        @endunless
    </span>
</a>
