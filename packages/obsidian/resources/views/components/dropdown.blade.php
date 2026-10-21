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

    {{-- Wide screens get an anchored popover; narrow ones a bottom sheet. Only one template is ever rendered. --}}
    <template x-if="isDesktop">
        <div
            x-show="open"
            x-anchor.{{ $position }}.offset.6.fixed="$refs.trigger"
            x-transition:enter="transition duration-200 ease-ob"
            x-transition:enter-start="opacity-0 scale-[.96]"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition duration-120 ease-ob"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-[.96]"
            data-ob-motion
            role="menu"
            tabindex="-1"
            aria-orientation="vertical"
            :class="isInsideLayer ? 'glass-solid' : 'glass'"
            class="{{ $transformOrigin }} z-50 min-w-48 rounded-xl p-1 focus:outline-none"
        >
            {{ $slot }}
        </div>
    </template>

    <template x-if="! isDesktop">
        <div
            x-show="open"
            x-trap.inert.noscroll="open"
            tabindex="-1"
            class="fixed inset-0 z-50 focus:outline-none"
        >
            <div
                x-show="open"
                x-on:click="close()"
                x-transition.opacity.duration.200ms
                data-ob-motion
                aria-hidden="true"
                class="scrim absolute inset-0"
            ></div>
            <div
                x-show="open"
                x-transition:enter="transition duration-[320ms] ease-ob"
                x-transition:enter-start="opacity-0 translate-y-full"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition duration-200 ease-ob"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-full"
                data-ob-motion
                data-ob-sheet-panel
                :style="dragY ? `transform: translateY(${dragY}px)` : null"
                :class="[isInsideLayer ? 'glass-solid' : 'glass', dragging ? 'transition-none' : '']"
                class="absolute inset-x-0 bottom-0 rounded-t-[20px] px-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))]"
            >
                <div
                    x-on:pointerdown="startDrag($event)"
                    x-on:pointermove="moveDrag($event)"
                    x-on:pointerup="endDrag()"
                    x-on:pointercancel="endDrag()"
                    class="flex h-6 touch-none items-center justify-center"
                >
                    <span class="h-1 w-9 rounded-full bg-hairline-strong"></span>
                </div>
                <div role="menu" tabindex="-1" aria-orientation="vertical" class="focus:outline-none">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>
</div>
