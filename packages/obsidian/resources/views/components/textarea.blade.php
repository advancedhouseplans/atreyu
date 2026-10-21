@props(['label' => null, 'hint' => null, 'rows' => 3])

@php
    use Illuminate\Support\Str;

    $errorKey = $attributes->get('name') ?? $attributes->wire('model')->value();
    $id = $attributes->get('id') ?? $errorKey ?? 'ob-'.Str::lower(Str::random(8));
    $grow = "\$el.style.height = 'auto'; \$el.style.height = \$el.scrollHeight + 'px'";
@endphp

<x-ob::field :label="$label" :hint="$hint" :error-key="$errorKey" :for="$id" :class="$attributes->get('class')">
    <textarea
        x-data
        x-init="{{ $grow }}"
        x-on:input="{{ $grow }}"
        {{ $attributes->except(['class', 'label', 'hint'])->merge(['id' => $id, 'rows' => $rows, 'aria-describedby' => $id.'-desc', 'class' => 'w-full resize-none overflow-hidden rounded-lg bg-surface-1 border border-hairline px-3 py-2 text-sm text-fg placeholder:text-fg-3 focus-ring in-data-invalid:border-danger disabled:opacity-50']) }}
    >{{ $slot }}</textarea>
</x-ob::field>
