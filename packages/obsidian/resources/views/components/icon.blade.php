@props(['name', 'variant' => 'outline'])

@php
    $prefix = ['outline' => 'o', 'solid' => 's', 'mini' => 'm', 'micro' => 'c'][$variant];
@endphp

{{ svg('heroicon-'.$prefix.'-'.$name, $attributes->class('size-5 shrink-0')->get('class'), ['aria-hidden' => 'true']) }}