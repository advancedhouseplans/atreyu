@blaze
@props(['heading'])

<div role="group" aria-label="{{ $heading }}" {{ $attributes }}>
    <div aria-hidden="true" class="px-2.5 pb-1 pt-2 text-xs font-medium text-fg-3">{{ $heading }}</div>
    {{ $slot }}
</div>
