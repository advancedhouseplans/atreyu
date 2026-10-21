@blaze
@props(['label', 'icon' => null])

<div {{ $attributes->merge(['class' => 'grid grid-cols-[8rem_1fr] items-center min-h-9 gap-3 max-sm:min-h-11']) }}>
    <div class="flex items-center gap-2 text-[13px] text-fg-3">
        @if ($icon)
            <x-ob::icon :name="$icon" variant="mini" class="size-4" />
        @endif
        {{ $label }}
    </div>
    <div class="min-w-0 text-sm text-fg wrap-break-word">{{ $slot }}</div>
</div>
