@props(['heading', 'description' => null])

<section {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <div class="flex items-end justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-base font-semibold tracking-[-0.01em]">{{ $heading }}</h2>
            @if ($description)
                <p class="mt-0.5 text-sm text-fg-2">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
    {{ $slot }}
</section>
