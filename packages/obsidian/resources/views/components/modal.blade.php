@props(['name' => null, 'title' => null, 'description' => null, 'size' => 'md', 'footer' => null, 'label' => null])

@php
    use Illuminate\Support\Str;

    $id = 'ob-modal-'.Str::lower(Str::random(8));
    $accessibleName = $label ?? $attributes->get('aria-label');
    $widthClass = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-lg', 'lg' => 'sm:max-w-2xl'][$size];
    $model = $attributes->wire('model');
    $modelProperty = $model->value();
    $openState = is_string($modelProperty) && $modelProperty !== '' ? "\$wire.\$entangle('".e(addslashes($modelProperty))."'".($model->hasModifier('live') ? ', true' : '').')' : 'false';
@endphp

<div
    x-data="obModal({ name: @js($name), open: {!! $openState !!} })"
    x-on:modal-open.window="onOpenEvent($event)"
    x-on:modal-close.window="onCloseEvent($event)"
    {{ $attributes->whereDoesntStartWith('wire:model')->except('aria-label') }}
>
    <div
        x-show="open"
        x-cloak
        x-trap.inert.noscroll="open"
        tabindex="-1"
        data-ob-layer="modal"
        class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6 focus:outline-none"
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
            x-transition:enter-start="opacity-0 max-sm:translate-y-full sm:scale-[.96]"
            x-transition:enter-end="opacity-100 max-sm:translate-y-0 sm:scale-100"
            x-transition:leave="transition duration-200 ease-ob"
            x-transition:leave-start="opacity-100 max-sm:translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 max-sm:translate-y-full sm:scale-[.96]"
            data-ob-motion
            role="dialog"
            aria-modal="true"
            @if ($title) aria-labelledby="{{ $id }}-title" @elseif ($accessibleName) aria-label="{{ $accessibleName }}" @endif
            @if ($description) aria-describedby="{{ $id }}-description" @endif
            class="glass relative flex max-h-[90dvh] w-full flex-col rounded-t-[20px] sm:rounded-xl {{ $widthClass }}"
        >
            @if ($title || $description)
                <div class="flex items-start gap-3 px-5 pb-2 pt-5">
                    <div class="min-w-0 flex-1">
                        @if ($title)
                            <h2 id="{{ $id }}-title" class="text-base font-semibold text-fg">{{ $title }}</h2>
                        @endif
                        @if ($description)
                            <p id="{{ $id }}-description" class="mt-1 text-sm text-fg-2">{{ $description }}</p>
                        @endif
                    </div>
                    <button
                        type="button"
                        x-on:click="close()"
                        aria-label="Close"
                        class="grid size-8 shrink-0 place-items-center rounded-full text-fg-3 transition duration-120 ease-ob hover:bg-surface-3 hover:text-fg focus-ring max-sm:size-11"
                    ><x-ob::icon name="x-mark" variant="mini" class="size-4" /></button>
                </div>
            @endif

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-3">
                {{ $slot }}
            </div>

            @if ($footer && ! $footer->isEmpty())
                <div class="flex flex-wrap items-center justify-end gap-2 px-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] pt-3 sm:pb-5">
                    {{ $footer }}
                </div>
            @else
                <div class="pb-[env(safe-area-inset-bottom)] sm:pb-2"></div>
            @endif
        </div>
    </div>
</div>
