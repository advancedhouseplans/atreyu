@props(['icon', 'heading'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 px-6 py-12 text-center']) }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-surface-3 text-fg-3">
        <x-ob::icon :name="$icon" class="size-6" />
    </span>
    <h3 class="text-base font-semibold">{{ $heading }}</h3>
    @if (! $slot->isEmpty())
        <p class="max-w-sm text-sm text-fg-2">{{ $slot }}</p>
    @endif
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
