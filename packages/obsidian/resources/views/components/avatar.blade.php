@blaze
@props(['src' => null, 'initials' => null, 'size' => 'md'])

@php
    $sizeClasses = ['sm' => 'size-6 text-[10px]', 'md' => 'size-8 text-xs', 'lg' => 'size-10 text-sm'][$size];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-surface-3 font-medium text-fg-2 {$sizeClasses}"]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="" class="size-full object-cover">
    @else
        {{ $initials }}
    @endif
</span>
