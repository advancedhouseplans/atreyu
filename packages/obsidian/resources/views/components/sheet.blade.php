@props(['name' => null, 'side' => 'bottom', 'title' => null, 'label' => null])

@php
    use Illuminate\Support\Str;

    $id = 'ob-sheet-'.Str::lower(Str::random(8));
    $isBottom = $side === 'bottom';
    $accessibleName = $label ?? $attributes->get('aria-label');
    $model = $attributes->wire('model');
    $modelProperty = $model->value();
    $openState = is_string($modelProperty) && $modelProperty !== '' ? "\$wire.\$entangle('".e(addslashes($modelProperty))."'".($model->hasModifier('live') ? ', true' : '').')' : 'false';

    $panelClasses = $isBottom
        ? 'inset-x-0 bottom-0 max-h-[90dvh] rounded-t-[20px]'
        : 'inset-y-0 right-0 w-full max-w-sm rounded-l-[20px]';
    $hiddenTransform = $isBottom ? 'translate-y-full' : 'translate-x-full';
    $shownTransform = $isBottom ? 'translate-y-0' : 'translate-x-0';
@endphp

<div
    x-data="obSheet({ name: @js($name), open: {!! $openState !!} })"
    x-on:modal-open.window="onOpenEvent($event)"
    x-on:modal-close.window="onCloseEvent($event)"
    {{ $attributes->whereDoesntStartWith('wire:model')->except('aria-label') }}
>
    <div
        x-show="open"
        x-cloak
        x-trap.inert.noscroll="open"
        tabindex="-1"
        data-ob-layer="sheet"
        class="fixed inset-0 z-50 focus:outline-none"
    >
        <div
            x-show="open"
            x-on:click="close()"
            x-transition:enter="transition-opacity duration-[320ms] ease-ob"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200 ease-ob"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            data-ob-motion
            aria-hidden="true"
            class="scrim absolute inset-0"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition duration-[320ms] ease-ob"
            x-transition:enter-start="opacity-0 {{ $hiddenTransform }}"
            x-transition:enter-end="opacity-100 {{ $shownTransform }}"
            x-transition:leave="transition duration-200 ease-ob"
            x-transition:leave-start="opacity-100 {{ $shownTransform }}"
            x-transition:leave-end="opacity-0 {{ $hiddenTransform }}"
            data-ob-motion
            data-ob-sheet-panel
            role="dialog"
            aria-modal="true"
            @if ($title) aria-labelledby="{{ $id }}-title" @elseif ($accessibleName) aria-label="{{ $accessibleName }}" @endif
            @if ($isBottom)
                :style="{ transform: dragY ? `translateY(${dragY}px)` : '' }"
                :class="settling ? 'transition-transform duration-200 ease-ob' : ''"
            @endif
            class="glass absolute flex flex-col {{ $panelClasses }}"
        >
            @if ($isBottom)
                <div
                    x-on:pointerdown="startDrag($event)"
                    x-on:pointermove="moveDrag($event)"
                    x-on:pointerup="endDrag($event)"
                    x-on:pointercancel="endDrag($event)"
                    class="touch-none"
                >
                    <div class="flex h-6 items-center justify-center">
                        <span class="h-1 w-9 rounded-full bg-hairline-strong"></span>
                    </div>
                    @if ($title)
                        <h2 id="{{ $id }}-title" class="px-5 pb-2 text-base font-semibold text-fg">{{ $title }}</h2>
                    @endif
                </div>
            @elseif ($title)
                <h2 id="{{ $id }}-title" class="px-5 pb-2 pt-5 text-base font-semibold text-fg">{{ $title }}</h2>
            @endif

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))]">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
