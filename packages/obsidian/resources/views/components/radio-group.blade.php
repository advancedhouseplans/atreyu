@props(['label' => null, 'hint' => null, 'options' => []])

@php
    $errorKey = $attributes->get('name') ?? ($attributes->wire('model')->value() ?: null);
    $name = $errorKey ?? 'ob-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $hasError = $errorKey !== null && isset($errors) && $errors->has($errorKey);
    $describedBy = ($hint || $hasError) ? $name.'-group-desc' : null;
@endphp

<x-ob::field :label="$label" :hint="$hint" :error-key="$errorKey" :for="$name.'-group'" :class="$attributes->get('class')">
    <div id="{{ $name }}-group" role="radiogroup" @if ($label) aria-label="{{ $label }}" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($hasError) aria-invalid="true" @endif class="flex flex-col gap-2">
        @foreach ($options as $option)
            <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm text-fg max-sm:min-h-11">
                <input
                    type="radio"
                    value="{{ $option['value'] }}"
                    {{ $attributes->except(['class', 'label', 'hint'])->merge(['name' => $name, 'class' => 'size-4 shrink-0 cursor-pointer appearance-none rounded-full border border-fg-3 bg-surface-1 transition duration-120 ease-ob checked:border-accent checked:bg-accent checked:bg-[radial-gradient(circle,var(--color-on-accent)_32%,transparent_36%)] focus-ring in-data-invalid:border-danger disabled:opacity-50']) }}
                >
                <span>{{ $option['label'] }}</span>
            </label>
        @endforeach
    </div>
</x-ob::field>
