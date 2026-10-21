{{-- `when` is an Alpine expression that gates the tooltip, e.g. only while a sidebar is collapsed. --}}
@props(['text', 'position' => 'top', 'when' => 'true'])

<span
    x-data="obTooltip"
    x-ref="trigger"
    x-on:mouseenter="show()"
    x-on:mouseleave="hide()"
    x-on:focusin="show()"
    x-on:focusout="hide()"
    x-on:keydown.escape="hide()"
    {{ $attributes->merge(['class' => 'inline-flex']) }}
>
    {{ $slot }}
    <span
        x-show="open && ({{ $when }})"
        x-cloak
        x-anchor.{{ $position }}.offset.6="$refs.trigger"
        x-transition.opacity.duration.120ms
        role="tooltip"
        data-ob-motion
        class="glass z-50 pointer-events-none whitespace-nowrap rounded-lg px-2 py-1 text-xs text-fg"
    >{{ $text }}</span>
</span>
