@props(['label' => null, 'hint' => null])

@php
    use Illuminate\Support\Str;

    $errorKey = $attributes->get('name') ?? $attributes->wire('model')->value();
    $id = $attributes->get('id') ?? $errorKey ?? 'ob-'.Str::lower(Str::random(8));
@endphp

<x-ob::field :hint="$hint" :error-key="$errorKey" :for="$id" :class="$attributes->get('class')">
    <label class="inline-flex cursor-pointer items-start gap-2.5 text-sm text-fg">
        <span class="relative mt-0.5 inline-flex shrink-0">
            <input
                type="checkbox"
                {{ $attributes->except(['class', 'label', 'hint'])->merge(['id' => $id, 'aria-describedby' => $id.'-desc', 'class' => 'peer size-4 cursor-pointer appearance-none rounded-[5px] border border-hairline-strong bg-surface-1 transition duration-120 ease-ob checked:border-accent checked:bg-accent focus-ring in-data-invalid:border-danger disabled:opacity-50']) }}
            >
            <x-ob::icon name="check" variant="micro" class="pointer-events-none absolute inset-0 m-auto size-3 text-on-accent opacity-0 peer-checked:opacity-100" />
        </span>
        @if ($label)
            <span>{{ $label }}</span>
        @endif
    </label>
</x-ob::field>
