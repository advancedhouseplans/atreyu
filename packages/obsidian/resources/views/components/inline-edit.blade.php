@props(['value' => null, 'action', 'name', 'type' => 'text', 'options' => [], 'label', 'placeholder' => null])

@php
    $shown = $type === 'select'
        ? (string) (collect($options)->first(fn ($option) => (string) $option['value'] === (string) $value)['label'] ?? '')
        : (string) $value;
    $fieldClasses = 'h-8 max-sm:h-11 min-w-0 flex-1 rounded-md bg-surface-1 border border-hairline-strong px-2 text-sm text-fg focus-ring';
@endphp

<div
    x-data="obInlineEdit({ value: @js($value), action: @js($action), name: @js($name), type: @js($type), options: @js($options) })"
    data-value="{{ $value }}"
    {{ $attributes->merge(['class' => 'min-w-0']) }}
>
    <div class="flex items-center gap-2">
        <button
            type="button"
            x-show="!editing"
            @click="start()"
            aria-label="Edit {{ $label }}"
            class="-mx-2 inline-flex min-h-8 min-w-0 max-w-full items-center rounded-md px-2 text-left text-sm transition duration-120 ease-ob hover:bg-surface-3 focus-ring max-sm:min-h-11"
        >
            <span class="truncate" :class="displayText() === '' ? 'text-fg-3' : 'text-fg'" x-text="displayText() || @js($placeholder ?? '')">{{ $shown !== '' ? $shown : $placeholder }}</span>
        </button>

        <div x-show="editing" x-cloak class="flex min-w-0 flex-1 items-center gap-2">
            @if ($type === 'select')
                <select
                    x-ref="field"
                    x-model="draft"
                    @change="commit()"
                    @blur="commit()"
                    aria-label="{{ $label }}"
                    :aria-invalid="error ? 'true' : null"
                    :class="error && 'border-danger'"
                    class="{{ $fieldClasses }}"
                >
                    @if ($placeholder !== null)
                        <option value="">{{ $placeholder }}</option>
                    @endif
                    @foreach ($options as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            @else
                <input
                    type="text"
                    x-ref="field"
                    x-model="draft"
                    @keydown.enter.prevent="commit()"
                    @blur="commit()"
                    aria-label="{{ $label }}"
                    :aria-invalid="error ? 'true' : null"
                    :class="error && 'border-danger'"
                    class="{{ $fieldClasses }}"
                >
            @endif
        </div>

        <x-ob::spinner x-show="saving" x-cloak class="size-4 text-fg-3" />
        <span x-show="saved" x-cloak x-transition.opacity.duration.200ms class="text-success-fg">
            <x-ob::icon name="check" variant="mini" class="size-4" />
        </span>
    </div>

    <template x-if="editing && error">
        <p role="alert" class="mt-1 text-xs text-danger-fg" x-text="error"></p>
    </template>
</div>
