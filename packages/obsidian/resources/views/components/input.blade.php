@props([
    'label' => null,
    'hint' => null,
    'icon' => null,
    'clearable' => false,
    'prefix' => null,
    'suffix' => null,
    'type' => 'text',
])

@php
    use Illuminate\Support\Str;

    $trailingIcon = $attributes->get('icon:trailing');
    $errorKey = $attributes->get('name') ?? $attributes->wire('model')->value();
    $id = $attributes->get('id') ?? $errorKey ?? 'ob-'.Str::lower(Str::random(8));
    $hasTrailing = $trailingIcon || $clearable || ! $slot->isEmpty();

    $controlClasses = 'h-9 max-sm:h-11 w-full min-w-0 rounded-lg bg-surface-1 border border-hairline px-3 text-sm text-fg placeholder:text-fg-3 focus-ring in-data-invalid:border-danger disabled:opacity-50'
        .($icon ? ' pl-9' : '')
        .($hasTrailing ? ' pr-9' : '')
        .($prefix ? ' rounded-l-none' : '')
        .($suffix ? ' rounded-r-none' : '');

    $addonClasses = 'inline-flex shrink-0 items-center border border-hairline bg-surface-3 px-3 text-sm text-fg-3';
@endphp

<x-ob::field :label="$label" :hint="$hint" :error-key="$errorKey" :for="$id" :class="$attributes->get('class')">
    <div class="flex" @if ($clearable) x-data="{ filled: false }" x-on:input="filled = $refs.control.value !== ''" x-init="filled = $refs.control.value !== ''" @endif>
        @if ($prefix)
            <span class="{{ $addonClasses }} rounded-l-lg border-r-0">{{ $prefix }}</span>
        @endif
        <div class="relative flex-1">
            @if ($icon)
                <x-ob::icon :name="$icon" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-fg-3" />
            @endif
            <input
                {{ $attributes->except(['class', 'label', 'hint', 'icon:trailing'])->merge(['id' => $id, 'type' => $type, 'aria-describedby' => $id.'-desc', 'class' => $controlClasses])->when($clearable, fn ($bag) => $bag->merge(['x-ref' => 'control'])) }}
            >
            <div class="absolute right-1.5 top-1/2 flex -translate-y-1/2 items-center gap-0.5 text-fg-3">
                @if ($clearable)
                    <button
                        type="button"
                        x-show="filled"
                        x-cloak
                        x-on:click="$refs.control.value = ''; $refs.control.dispatchEvent(new Event('input', { bubbles: true })); $refs.control.focus()"
                        aria-label="Clear"
                        class="inline-flex size-6 items-center justify-center rounded-full transition duration-120 ease-ob hover:bg-surface-3 hover:text-fg focus-ring"
                    ><x-ob::icon name="x-mark" variant="mini" class="size-4" /></button>
                @endif
                @if ($trailingIcon)
                    <x-ob::icon :name="$trailingIcon" variant="mini" class="pointer-events-none mr-1.5 size-4" />
                @endif
                {{ $slot }}
            </div>
        </div>
        @if ($suffix)
            <span class="{{ $addonClasses }} rounded-r-lg border-l-0">{{ $suffix }}</span>
        @endif
    </div>
</x-ob::field>
