@props(['title'])

<header {{ $attributes->class(['mb-7 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    <h1 class="text-2xl font-semibold tracking-[-0.03em] text-base-content sm:text-[1.65rem]">{{ $title }}</h1>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</header>
