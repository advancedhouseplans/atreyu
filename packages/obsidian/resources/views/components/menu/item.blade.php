@blaze
@props(['icon' => null, 'href' => null, 'navigate' => false, 'variant' => null, 'shortcut' => null])

@php
    $classes = 'flex w-full items-center gap-2.5 h-9 max-sm:h-11 rounded-lg px-2.5 text-left text-sm transition duration-120 ease-ob hover:bg-surface-3 focus:bg-surface-3 focus-ring disabled:opacity-50 aria-disabled:opacity-50 '
        .($variant === 'danger' ? 'text-danger-fg' : 'text-fg');

    $rootAttributes = $attributes->merge(['role' => 'menuitem', 'tabindex' => '-1', 'class' => $classes]);

    if ($href !== null) {
        $rootAttributes = $rootAttributes->merge(['href' => $href]);

        if ($navigate) {
            $rootAttributes = $rootAttributes->merge(['wire:navigate' => true]);
        }
    } else {
        $rootAttributes = $rootAttributes->merge(['type' => 'button']);
    }
@endphp

@if ($href !== null)
    <a {{ $rootAttributes }}>
@else
    <button {{ $rootAttributes }}>
@endif
    @if ($icon)
        <x-ob::icon :name="$icon" variant="mini" class="size-4 shrink-0 {{ $variant === 'danger' ? '' : 'text-fg-3' }}" />
    @endif
    <span class="flex-1 truncate">{{ $slot }}</span>
    @if ($shortcut)
        <x-ob::kbd class="ml-auto">{{ $shortcut }}</x-ob::kbd>
    @endif
@if ($href !== null)
    </a>
@else
    </button>
@endif
