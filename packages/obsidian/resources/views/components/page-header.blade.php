@props(['title', 'description' => null])

{{-- display:contents keeps the sticky compact bar a direct layout child of the page wrapper. --}}
<div x-data="{ compact: false }" class="contents">
    {{-- Zero net height (-mb-12) and opacity-toggled, so showing it never shifts the title and retriggers the observer. Only a title that left through the top compacts it (not one still below the fold). --}}
    <div
        aria-hidden="true"
        x-bind:inert="!compact"
        x-bind:class="compact ? 'opacity-100' : 'pointer-events-none opacity-0'"
        class="glass sticky top-0 z-10 -mb-12 flex h-12 items-center justify-center px-4 transition-opacity duration-200 ease-ob sm:hidden"
        data-ob-motion
    >
        <span class="truncate text-base font-semibold" x-text="@js($title)"></span>
    </div>

    <div
        x-intersect:leave="compact = $el.getBoundingClientRect().top < 0"
        x-intersect:enter="compact = false"
        {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-3 pb-6']) }}
    >
        <div class="min-w-0">
            <h1 class="text-[28px] font-semibold leading-tight tracking-[-0.02em] sm:text-2xl">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 text-sm text-fg-2">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
