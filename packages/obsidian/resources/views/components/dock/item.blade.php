@props(['href', 'icon', 'label', 'active' => null])

@php
    $path = trim((string) parse_url($href, PHP_URL_PATH), '/');
    $active ??= request()->url() === $href || ($path === '' ? request()->is('/') : request()->is($path, $path.'/*'));

    $classes = 'flex size-11 items-center justify-center rounded-[14px] transition duration-120 ease-ob focus-ring '
        .($active ? 'bg-accent-soft text-accent-fg' : 'text-fg-2 hover:bg-surface-3');
@endphp

{{-- The label is aria-label only: no visible or sr-only text, so each label appears once on the page. --}}
<a
    href="{{ $href }}"
    wire:navigate
    aria-label="{{ $label }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    <x-ob::icon :name="$icon" class="size-6" />
</a>
