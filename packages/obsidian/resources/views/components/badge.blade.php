@blaze
@props(['color' => 'neutral', 'dot' => false])

@php
    $colorClasses = [
        'neutral' => 'bg-neutral-soft text-neutral-fg',
        'danger' => 'bg-danger-soft text-danger-fg',
        'warning' => 'bg-warning-soft text-warning-fg',
        'success' => 'bg-success-soft text-success-fg',
        'info' => 'bg-info-soft text-info-fg',
        'accent' => 'bg-accent-soft text-accent-fg',
    ][$color];

    $dotClass = [
        'neutral' => 'bg-neutral',
        'danger' => 'bg-danger',
        'warning' => 'bg-warning',
        'success' => 'bg-success',
        'info' => 'bg-info',
        'accent' => 'bg-accent',
    ][$color];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 min-h-5 text-xs font-medium {$colorClasses}"]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
