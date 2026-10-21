@props([
    'sidebar' => false,
])

<a {{ $attributes->class(['flex items-center gap-3']) }}>
    <span @class([
        'grid shrink-0 place-items-center bg-primary text-primary-content shadow-sm shadow-primary/20',
        'size-9 rounded-lg' => $sidebar,
        'size-10 rounded-xl' => ! $sidebar,
    ])>
        <x-app-logo-icon @class(['size-6' => $sidebar, 'size-7' => ! $sidebar]) />
    </span>
    <span @class(['leading-none', 'mary-hideable' => $sidebar])>
        <span class="block text-lg font-bold tracking-tight">Atreyu</span>
        @unless ($sidebar)
            <span class="mt-1 block text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-base-content/50">Advanced House Plans</span>
        @endunless
    </span>
</a>
