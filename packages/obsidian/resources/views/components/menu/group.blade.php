@blaze
@props(['label' => null])

<div role="group" @if ($label) aria-label="{{ $label }}" @endif {{ $attributes }}>
    @if ($label)
        <div aria-hidden="true" class="px-2.5 pb-1 pt-2 text-xs font-medium text-fg-3">{{ $label }}</div>
    @endif
    {{ $slot }}
</div>
