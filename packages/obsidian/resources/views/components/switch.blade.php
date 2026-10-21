@props(['label' => null, 'hint' => null])

@php
    use Illuminate\Support\Str;

    $errorKey = $attributes->get('name') ?? ($attributes->wire('model')->value() ?: null);
    $id = $attributes->get('id') ?? $errorKey ?? 'ob-'.Str::lower(Str::random(8));
    $hasError = $errorKey !== null && isset($errors) && $errors->has($errorKey);
    $describedBy = ($hint || $hasError) ? $id.'-desc' : null;
@endphp

<x-ob::field :hint="$hint" :error-key="$errorKey" :for="$id" :class="$attributes->get('class')">
    <label class="inline-flex cursor-pointer items-center gap-3 text-sm text-fg max-sm:min-h-11">
        <input
            type="checkbox"
            role="switch"
            {{ $attributes->except(['class', 'label', 'hint'])->merge(['id' => $id, 'aria-describedby' => $describedBy, 'aria-invalid' => $hasError ? 'true' : null, 'class' => 'peer sr-only']) }}
        >
        <span
            aria-hidden="true"
            class="relative h-5 w-9 shrink-0 rounded-full border border-hairline-strong bg-surface-3 transition duration-200 ease-ob after:absolute after:left-0.5 after:top-0.5 after:size-3.5 after:rounded-full after:bg-fg-3 after:transition after:duration-200 after:ease-ob peer-checked:border-accent peer-checked:bg-accent peer-checked:after:translate-x-4 peer-checked:after:bg-on-accent peer-focus-visible:shadow-[0_0_0_1px_var(--color-accent),0_0_0_5px_var(--color-accent-soft)] peer-disabled:opacity-50 motion-reduce:transition-none motion-reduce:after:transition-none"
        ></span>
        @if ($label)
            <span>{{ $label }}</span>
        @endif
    </label>
</x-ob::field>
