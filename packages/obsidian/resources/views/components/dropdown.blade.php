@props(['position' => 'bottom-end'])

@php
    $transformOrigin = str_starts_with($position, 'top') ? 'origin-bottom' : 'origin-top';
@endphp

<div
    x-data="obDropdown"
    x-effect="syncTrigger()"
    x-on:keydown="onKeydown($event)"
    x-on:click.outside="close(false)"
    x-on:resize.window.debounce.100ms="syncViewport()"
    {{ $attributes->merge(['class' => 'relative inline-flex']) }}
>
    <span data-ob-trigger x-ref="trigger" x-on:click="toggle($event)" class="inline-flex">{{ $trigger }}</span>

    {{-- One panel and one copy of the slot: an anchored popover at sm and up, a bottom sheet below (the inline styles x-anchor writes are overridden with !important classes). --}}
    <div
        x-show="open"
        x-trap.inert.noscroll="open && ! isDesktop"
        tabindex="-1"
        class="z-50 focus:outline-none max-sm:fixed max-sm:inset-0 sm:contents"
    >
        <div
            x-show="open"
            x-on:click="close()"
            x-transition.opacity.duration.200ms
            data-ob-motion
            aria-hidden="true"
            class="scrim absolute inset-0 sm:hidden"
        ></div>
        <div
            x-show="open"
            x-anchor.{{ $position }}.offset.6.fixed="$refs.trigger"
            x-transition:enter="transition duration-200 max-sm:duration-[320ms] ease-ob"
            x-transition:enter-start="opacity-0 sm:scale-[.96] max-sm:translate-y-full"
            x-transition:enter-end="opacity-100 sm:scale-100 max-sm:translate-y-0"
            x-transition:leave="transition duration-120 max-sm:duration-200 ease-ob"
            x-transition:leave-start="opacity-100 sm:scale-100 max-sm:translate-y-0"
            x-transition:leave-end="opacity-0 sm:scale-[.96] max-sm:translate-y-full"
            data-ob-motion
            data-ob-sheet-panel
            :style="{ transform: dragY ? `translateY(${dragY}px)` : '' }"
            :class="[isInsideLayer ? 'glass-solid' : 'glass', settling ? 'transition-transform duration-200 ease-ob' : '']"
            class="{{ $transformOrigin }} z-50 min-w-48 rounded-xl p-1 max-sm:!fixed max-sm:!inset-x-0 max-sm:!bottom-0 max-sm:!left-0 max-sm:!top-auto max-sm:rounded-b-none max-sm:rounded-t-[20px] max-sm:px-2 max-sm:pb-[calc(0.5rem+env(safe-area-inset-bottom))]"
        >
            <div
                x-on:pointerdown="startDrag($event)"
                x-on:pointermove="moveDrag($event)"
                x-on:pointerup="endDrag($event)"
                x-on:pointercancel="endDrag($event)"
                class="flex h-6 touch-none items-center justify-center sm:hidden"
            >
                <span class="h-1 w-9 rounded-full bg-hairline-strong"></span>
            </div>
            <div
                role="menu"
                tabindex="-1"
                aria-orientation="vertical"
                x-on:click="onMenuClick($event)"
                class="focus:outline-none"
            >
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
