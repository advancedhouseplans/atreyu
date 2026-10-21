@props(['label' => null, 'hint' => null, 'options' => [], 'placeholder' => null])

@php
    use Illuminate\Support\Str;

    $errorKey = $attributes->get('name') ?? ($attributes->wire('model')->value() ?: null);
    $id = $attributes->get('id') ?? $errorKey ?? 'ob-'.Str::lower(Str::random(8));
    $hasError = $errorKey !== null && isset($errors) && $errors->has($errorKey);
    $describedBy = ($hint || $hasError) ? $id.'-desc' : null;
@endphp

<x-ob::field :label="$label" :hint="$hint" :error-key="$errorKey" :for="$id" :class="$attributes->get('class')">
    <div class="relative">
        <select
            {{ $attributes->except(['class', 'label', 'hint'])->merge(['id' => $id, 'aria-describedby' => $describedBy, 'aria-invalid' => $hasError ? 'true' : null, 'class' => 'h-9 max-sm:h-11 w-full appearance-none rounded-lg bg-surface-1 border border-hairline pl-3 pr-9 text-sm text-fg focus-ring in-data-invalid:border-danger disabled:opacity-50']) }}
        >
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <x-ob::icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-fg-3" />
    </div>
</x-ob::field>
