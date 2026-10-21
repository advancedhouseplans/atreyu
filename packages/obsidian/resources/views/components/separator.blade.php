@blaze
@props(['vertical' => false, 'label' => null])

@if ($vertical)
    <div role="separator" aria-orientation="vertical" {{ $attributes->merge(['class' => 'w-px self-stretch bg-hairline']) }}></div>
@elseif ($label)
    <div role="separator" {{ $attributes->merge(['class' => 'flex items-center gap-3 text-xs text-fg-3']) }}>
        <span class="h-px flex-1 bg-hairline"></span>
        {{ $label }}
        <span class="h-px flex-1 bg-hairline"></span>
    </div>
@else
    <div role="separator" {{ $attributes->merge(['class' => 'h-px w-full bg-hairline']) }}></div>
@endif
