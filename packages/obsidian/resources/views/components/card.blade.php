@blaze
@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-hairline bg-surface-1 shadow-[0_1px_2px_rgb(0_0_0/.04),0_4px_12px_rgb(0_0_0/.04)] dark:shadow-none'.($padding ? ' p-5 max-sm:p-4' : '')]) }}>
    {{ $slot }}
</div>
