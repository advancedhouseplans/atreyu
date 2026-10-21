@blaze
@props(['href' => null])

{{--
    With `href`, the first cell's content is wrapped in the stretched link. That cell must not contain interactive
    elements (links, buttons, inline-edit) and must not be mobile="hide". Other interactive cells need `relative z-10`.
--}}

@php
    $content = (string) $slot;

    // ponytail: the link wraps the first cell's HTML via regex; a nested <td> in that cell would end the match early.
    if ($href !== null) {
        $link = '<a href="'.e($href).'" wire:navigate class="after:absolute after:inset-0 focus-ring">';
        $content = preg_replace('/(<td\b[^>]*>)(.*?)(<\/td>)/s', '$1'.addcslashes($link, '\\$').'$2</a>$3', $content, 1);
    }
@endphp

<tr {{ $attributes->merge(['class' => 'relative h-10 border-t border-hairline text-fg transition-colors duration-120 ease-ob hover:bg-surface-3 max-sm:grid max-sm:h-auto max-sm:grid-cols-[repeat(4,minmax(0,max-content))] max-sm:justify-start max-sm:gap-x-3 max-sm:gap-y-0.5 max-sm:px-4 max-sm:py-3']) }}>{!! $content !!}</tr>
