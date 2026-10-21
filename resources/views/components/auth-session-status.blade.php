@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-success-soft p-3 text-sm font-medium text-success-fg']) }}>
        {{ $status }}
    </div>
@endif
