@props([
    'options' => [],
    'multiple' => false,
    'placeholder' => 'Select…',
    'label' => null,
    'hint' => null,
    'search' => null,
])

@php
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;
    use Illuminate\View\ComponentAttributeBag;

    $model = $attributes->wire('model');
    $modelProperty = ($model->value() ?: null);
    $errorKey = $attributes->get('name') ?? $modelProperty;
    $stableName = $modelProperty ?? $search ?? $attributes->get('name');
    $id = $attributes->get('id') ?? 'ob-cb-'.($stableName !== null ? Str::slug($stableName) : Str::lower(Str::random(8)));
    $entangled = $modelProperty !== null
        ? "\$wire.\$entangle('".e(addslashes($modelProperty))."'".($model->hasModifier('live') ? ', true' : '').')'
        : 'null';
    $config = '{ id: '.Js::from($id).', multiple: '.($multiple ? 'true' : 'false').', server: '.($search ? 'true' : 'false').', value: '.$entangled.' }';

    $searchAttributes = $search ? ['wire:model.live.debounce.250ms' => $search] : [];
    $inputAttributes = new ComponentAttributeBag([
        'type' => 'text',
        'role' => 'combobox',
        'autocomplete' => 'off',
        'aria-autocomplete' => 'list',
        'aria-haspopup' => 'listbox',
        'aria-controls' => $id.'-listbox',
        ':aria-expanded' => 'open',
        ':aria-activedescendant' => 'activeId',
        'x-on:input' => 'onInput($event)',
        'x-on:keydown' => 'onKeydown($event)',
        ':value' => 'query',
        'data-ob-combobox-input' => true,
        ...$searchAttributes,
    ]);
@endphp

<x-ob::field :label="$label" :hint="$hint" :error-key="$errorKey" :for="$id" :class="$attributes->get('class')">
    <div
        x-data="obCombobox({!! $config !!})"
        @unless ($modelProperty !== null) x-modelable="value" @endunless
        data-options="{{ json_encode(array_values($options)) }}"
        x-on:click.outside="close()"
        x-on:resize.window.debounce.100ms="syncViewport()"
        class="relative"
        {{ $attributes->whereDoesntStartWith('wire:model')->except(['class', 'id', 'name']) }}
    >
        <div
            x-ref="control"
            x-on:click="onControlClick($event)"
            class="flex min-h-9 flex-wrap items-center gap-1 rounded-lg border border-hairline bg-surface-1 px-2 py-1 text-sm in-data-invalid:border-danger focus-within:shadow-[0_0_0_1px_var(--color-accent),0_0_0_5px_var(--color-accent-soft)] max-sm:min-h-11"
        >
            @if ($multiple)
                <template x-for="option in selectedOptions" :key="option.value">
                    <x-ob::badge>
                        <span x-text="option.label"></span>
                        <button
                            type="button"
                            x-on:click="remove(option)"
                            :aria-label="'Remove ' + option.label"
                            class="relative -mr-1 grid size-4 place-items-center rounded-full text-fg-3 hover:text-fg focus-ring max-sm:before:absolute max-sm:before:-inset-3 max-sm:before:content-['']"
                        ><x-ob::icon name="x-mark" variant="micro" class="size-3" /></button>
                    </x-ob::badge>
                </template>
            @endif

            <input
                id="{{ $id }}"
                x-ref="input"
                :placeholder="placeholderText ?? @js($placeholder)"
                :class="placeholderText && 'placeholder:text-fg'"
                {{ $inputAttributes->merge(['class' => 'min-w-24 flex-1 bg-transparent py-0.5 text-fg placeholder:text-fg-3 focus:outline-none max-sm:hidden']) }}
            >

            {{-- Below sm the inline input is hidden and the search lives in the sheet, so the control opens it instead. --}}
            <button
                type="button"
                x-on:click="show()"
                :aria-expanded="open"
                aria-haspopup="listbox"
                class="min-h-9 min-w-0 flex-1 truncate text-left text-fg-3 sm:hidden"
                :class="placeholderText && 'text-fg'"
                x-text="placeholderText ?? @js($placeholder)"
            ></button>

            <x-ob::icon name="chevron-up-down" variant="mini" class="pointer-events-none ml-auto size-4 shrink-0 text-fg-3" />
        </div>

        {{-- One panel and one copy of the list: an anchored popover at sm and up, a bottom sheet below. --}}
        <div
            x-show="open"
            x-cloak
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
                x-anchor.bottom-start.offset.4.fixed="$refs.control"
                x-transition:enter="transition duration-200 max-sm:duration-[320ms] ease-ob"
                x-transition:enter-start="opacity-0 sm:scale-[.96] max-sm:translate-y-full"
                x-transition:enter-end="opacity-100 sm:scale-100 max-sm:translate-y-0"
                x-transition:leave="transition duration-120 max-sm:duration-200 ease-ob"
                x-transition:leave-start="opacity-100 sm:scale-100 max-sm:translate-y-0"
                x-transition:leave-end="opacity-0 sm:scale-[.96] max-sm:translate-y-full"
                data-ob-motion
                data-ob-sheet-panel
                :style="{ transform: dragY ? `translateY(${dragY}px)` : '', minWidth: isDesktop ? panelWidth + 'px' : '' }"
                :class="[isInsideLayer ? 'glass-solid' : 'glass', settling ? 'transition-transform duration-200 ease-ob' : '']"
                class="origin-top z-50 rounded-xl p-1 max-sm:!fixed max-sm:!inset-x-0 max-sm:!bottom-0 max-sm:!left-0 max-sm:!top-auto max-sm:rounded-b-none max-sm:rounded-t-[20px] max-sm:px-2 max-sm:pb-[calc(0.5rem+env(safe-area-inset-bottom))]"
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
                <input
                    aria-label="{{ $label ?? 'Search' }}"
                    placeholder="Search…"
                    {{ $inputAttributes->merge(['class' => 'mb-1 h-11 w-full rounded-lg border border-hairline bg-surface-1 px-3 text-sm text-fg placeholder:text-fg-3 focus-ring sm:hidden']) }}
                >
                <ul
                    x-ref="list"
                    id="{{ $id }}-listbox"
                    role="listbox"
                    @if ($multiple) aria-multiselectable="true" @endif
                    @if ($label) aria-label="{{ $label }}" @endif
                    class="max-h-64 overflow-y-auto max-sm:max-h-[50dvh]"
                >
                    <template x-for="(option, index) in results" :key="option.value">
                        <li
                            role="option"
                            :id="id + '-option-' + index"
                            :aria-selected="isSelected(option)"
                            :data-active="index === active"
                            x-on:click="toggle(option)"
                            x-on:mousemove="active = index"
                            class="flex min-h-9 cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm text-fg data-[active=true]:bg-surface-3 max-sm:min-h-11"
                        >
                            <img x-show="option.avatar" :src="option.avatar" alt="" class="size-6 shrink-0 rounded-full object-cover">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate" x-text="option.label"></span>
                                <span x-show="option.description" class="block truncate text-xs text-fg-3" x-text="option.description"></span>
                            </span>
                            <x-ob::icon x-show="isSelected(option)" name="check" variant="mini" class="size-4 shrink-0 text-accent-fg" />
                        </li>
                    </template>
                    <li x-show="results.length === 0" role="presentation" class="px-2.5 py-3 text-sm text-fg-3">No results</li>
                </ul>
            </div>
        </div>
    </div>
</x-ob::field>
