@props(['href', 'icon', 'label', 'badge' => null, 'active' => null])

@php
    $path = trim((string) parse_url($href, PHP_URL_PATH), '/');
    $active ??= request()->url() === $href || ($path === '' ? request()->is('/') : request()->is($path, $path.'/*'));

    $classes = 'flex h-9 w-full items-center gap-3 rounded-lg px-3.5 text-sm font-medium transition duration-120 ease-ob focus-ring '
        .($active ? 'bg-surface-3 text-fg' : 'text-fg-2 hover:bg-surface-3 hover:text-fg');
@endphp

{{-- The label span is multi-line on purpose: the tooltip carries the exact label text node, so a page only has it once. --}}
<x-ob::tooltip :text="$label" position="right" when="collapsed" class="w-full">
    <a
        href="{{ $href }}"
        wire:navigate
        aria-label="{{ $label }}"
        @if ($active) aria-current="page" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >
        <x-ob::icon :name="$icon" class="size-5 {{ $active ? 'text-accent-fg' : 'text-fg-3' }}" />
        <span x-show="!collapsed" class="flex-1 truncate">
            {{ $label }}
        </span>
        @if ($badge !== null)
            <x-ob::badge x-show="!collapsed" color="accent">{{ $badge }}</x-ob::badge>
        @endif
    </a>
</x-ob::tooltip>
