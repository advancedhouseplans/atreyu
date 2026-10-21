@blaze
@props(['mobile' => 'meta'])

@php
    $mobileClasses = [
        'primary' => 'max-sm:order-first max-sm:col-span-full max-sm:text-sm max-sm:text-fg',
        'meta' => 'max-sm:text-xs max-sm:text-fg-3',
        'hide' => 'max-sm:hidden',
    ][$mobile];
@endphp

<td {{ $attributes->merge(['class' => "min-w-0 truncate px-3 py-2 max-sm:p-0 {$mobileClasses}"]) }}>{{ $slot }}</td>
