@props(['label' => null, 'hint' => null])

@php
    use Illuminate\Support\Str;

    $errorKey = $attributes->get('name') ?? ($attributes->wire('model')->value() ?: null);
    $id = $attributes->get('id') ?? $errorKey ?? 'ob-'.Str::lower(Str::random(8));
    $hasError = $errorKey !== null && isset($errors) && $errors->has($errorKey);
    $describedBy = ($hint || $hasError) ? $id.'-desc' : null;
@endphp

<x-ob::field :hint="$hint" :error-key="$errorKey" :for="$id" :class="$attributes->get('class')">
    <label class="inline-flex cursor-pointer items-start gap-2.5 text-sm text-fg max-sm:min-h-11 max-sm:items-center">
        <span class="relative mt-0.5 inline-flex shrink-0 max-sm:mt-0">
            <input
                type="checkbox"
                {{ $attributes->except(['class', 'label', 'hint'])->merge(['id' => $id, 'aria-describedby' => $describedBy, 'aria-invalid' => $hasError ? 'true' : null, 'class' => 'peer size-4 cursor-pointer appearance-none rounded-[5px] border border-fg-3 bg-surface-1 transition duration-120 ease-ob checked:border-accent checked:bg-accent focus-ring in-data-invalid:border-danger disabled:opacity-50']) }}
            >
            <x-ob::icon name="check" variant="micro" class="pointer-events-none absolute inset-0 m-auto size-3 text-on-accent opacity-0 peer-checked:opacity-100" />
        </span>
        @if ($label)
            <span>{{ $label }}</span>
        @endif
    </label>
</x-ob::field>
