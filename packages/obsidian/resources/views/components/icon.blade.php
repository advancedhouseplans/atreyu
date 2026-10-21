@props(['name', 'variant' => 'outline'])

@php
    $prefix = ['outline' => 'o', 'solid' => 's', 'mini' => 'm', 'micro' => 'c'][$variant];
    $callerClass = (string) $attributes->get('class', '');
    $hasSizeUtility = preg_match('/(^|\s)(?:[\w-]+:)*(size|w|h)-/', $callerClass) === 1;
    $classes = trim(($hasSizeUtility ? '' : 'size-5 ').'shrink-0 '.$callerClass);
@endphp

{{ svg('heroicon-'.$prefix.'-'.$name, $classes, $attributes->except('class')->getAttributes()) }}