@blaze
@props(['href', 'icon' => null, 'meta' => null])

<a
    href="{{ $href }}"
    wire:navigate
    role="option"
    tabindex="-1"
    aria-selected="false"
    data-ob-command-item
    @if ($icon) data-icon="{{ $icon }}" @endif
    {{ $attributes->merge(['class' => 'flex min-h-9 items-center gap-2.5 rounded-lg px-2.5 text-sm text-fg aria-selected:bg-surface-3 max-sm:min-h-11']) }}
>
    @if ($icon)
        <x-ob::icon :name="$icon" variant="mini" class="size-4 shrink-0 text-fg-3" />
    @endif
    <span data-ob-label class="min-w-0 flex-1 truncate">{{ $slot }}</span>
    @if ($meta)
        <span class="shrink-0 text-xs text-fg-3">{{ $meta }}</span>
    @endif
</a>
