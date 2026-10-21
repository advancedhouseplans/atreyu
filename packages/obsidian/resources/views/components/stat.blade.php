@blaze
@props(['label', 'value', 'icon' => null, 'selected' => false])

<button
    type="button"
    aria-pressed="{{ $selected ? 'true' : 'false' }}"
    {{ $attributes->merge(['class' => 'flex w-full flex-col items-start gap-1 rounded-xl border p-4 text-left transition duration-120 ease-ob focus-ring '.($selected ? 'border-accent bg-accent-soft' : 'border-hairline bg-surface-1 hover:bg-surface-3')]) }}
>
    <span class="flex items-center gap-1.5 text-sm text-fg-2">
        @if ($icon)
            <x-ob::icon :name="$icon" variant="mini" class="size-4 text-fg-3" />
        @endif
        {{ $label }}
    </span>
    <span class="text-3xl font-semibold tabular-nums tracking-[-0.04em]">{{ $value }}</span>
</button>
