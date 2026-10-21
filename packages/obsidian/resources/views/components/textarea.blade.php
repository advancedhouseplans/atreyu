@props(['label' => null, 'hint' => null, 'rows' => 3])

@php
    use Illuminate\Support\Str;

    $errorKey = $attributes->get('name') ?? ($attributes->wire('model')->value() ?: null);
    $id = $attributes->get('id') ?? $errorKey ?? 'ob-'.Str::lower(Str::random(8));
    $hasError = $errorKey !== null && isset($errors) && $errors->has($errorKey);
    $describedBy = ($hint || $hasError) ? $id.'-desc' : null;
    // A hidden textarea (modal, sheet) reports scrollHeight 0, so only measure while it is laid out.
    $grow = "if (\$el.scrollHeight > 0) { \$el.style.height = 'auto'; \$el.style.height = \$el.scrollHeight + 'px' }";
@endphp

<x-ob::field :label="$label" :hint="$hint" :error-key="$errorKey" :for="$id" :class="$attributes->get('class')">
    <textarea
        x-data
        x-init="$nextTick(() => { {{ $grow }} })"
        x-intersect="{{ $grow }}"
        x-on:input="{{ $grow }}"
        {{ $attributes->except(['class', 'label', 'hint'])->merge(['id' => $id, 'rows' => $rows, 'aria-describedby' => $describedBy, 'aria-invalid' => $hasError ? 'true' : null, 'class' => 'w-full resize-none overflow-hidden rounded-lg bg-surface-1 border border-hairline px-3 py-2 text-sm text-fg placeholder:text-fg-3 focus-ring in-data-invalid:border-danger disabled:opacity-50']) }}
    >{{ $slot }}</textarea>
</x-ob::field>
