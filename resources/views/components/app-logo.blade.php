@props([
    'sidebar' => false,
])

<a {{ $attributes->class(['flex items-center gap-3']) }}>
    <span @class([
        'grid shrink-0 place-items-center bg-accent text-on-accent',
        'size-8 rounded-lg' => $sidebar,
        'size-10 rounded-xl' => ! $sidebar,
    ])>
        <x-app-logo-icon @class(['size-5' => $sidebar, 'size-7' => ! $sidebar]) />
    </span>
    <span class="leading-none">
        <span class="block text-lg font-semibold tracking-tight text-fg">Atreyu</span>
        @unless ($sidebar)
            <span class="mt-1 block text-[0.65rem] font-medium uppercase tracking-[0.22em] text-fg-3">Advanced House Plans</span>
        @endunless
    </span>
</a>
