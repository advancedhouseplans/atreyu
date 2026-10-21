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
    $errorKey = $attributes->get('name') ?? ($attributes->wire('model')->value() ?: null);
    $id = $attributes->get('id') ?? $errorKey ?? 'ob-'.Str::lower(Str::random(8));
    $hasError = $errorKey !== null && isset($errors) && $errors->has($errorKey);
    $describedBy = ($hint || $hasError) ? $id.'-desc' : null;
    $hasTrailing = $trailingIcon || ! $slot->isEmpty();

    $controlClasses = 'peer h-9 max-sm:h-11 w-full min-w-0 rounded-lg bg-surface-1 border border-hairline px-3 text-sm text-fg placeholder:text-fg-3 focus-ring in-data-invalid:border-danger disabled:opacity-50'
        .($icon ? ' pl-9' : '')
        .($hasTrailing && $clearable ? ' pr-16 max-sm:pr-20' : ($hasTrailing || $clearable ? ' pr-9 max-sm:pr-11' : ''))
        .($prefix ? ' rounded-l-none' : '')
        .($suffix ? ' rounded-r-none' : '');

    $addonClasses = 'inline-flex shrink-0 items-center border border-hairline bg-surface-3 px-3 text-sm text-fg-3';
@endphp

<x-ob::field :label="$label" :hint="$hint" :error-key="$errorKey" :for="$id" :class="$attributes->get('class')">
    <div class="flex">
        @if ($prefix)
            <span class="{{ $addonClasses }} rounded-l-lg border-r-0">{{ $prefix }}</span>
        @endif
        <div class="relative flex-1">
            @if ($icon)
                <x-ob::icon :name="$icon" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-fg-3" />
            @endif
            <input
                @if ($clearable && ! $attributes->has('placeholder')) placeholder=" " @endif
                {{ $attributes->except(['class', 'label', 'hint', 'icon:trailing'])->merge(['id' => $id, 'type' => $type, 'aria-describedby' => $describedBy, 'aria-invalid' => $hasError ? 'true' : null, 'class' => $controlClasses]) }}
            >
            @if ($clearable)
                {{-- Visible only while the input is not showing its (blank) placeholder, so no JS state goes stale after a morph. --}}
                <button
                    type="button"
                    x-data
                    x-on:click="const control = $el.parentElement.querySelector('input'); control.value = ''; control.dispatchEvent(new Event('input', { bubbles: true })); control.focus()"
                    aria-label="Clear"
                    class="absolute top-1/2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-full text-fg-3 transition duration-120 ease-ob hover:bg-surface-3 hover:text-fg focus-ring peer-not-placeholder-shown:inline-flex max-sm:size-11 {{ $hasTrailing ? 'right-8 max-sm:right-11' : 'right-1.5 max-sm:right-0' }}"
                ><x-ob::icon name="x-mark" variant="mini" class="size-4" /></button>
            @endif
            @if ($hasTrailing)
                <div class="absolute right-1.5 top-1/2 flex -translate-y-1/2 items-center gap-0.5 text-fg-3 max-sm:right-0">
                    @if ($trailingIcon)
                        <x-ob::icon :name="$trailingIcon" variant="mini" class="pointer-events-none mr-1.5 size-4" />
                    @endif
                    {{ $slot }}
                </div>
            @endif
        </div>
        @if ($suffix)
            <span class="{{ $addonClasses }} rounded-r-lg border-l-0">{{ $suffix }}</span>
        @endif
    </div>
</x-ob::field>
