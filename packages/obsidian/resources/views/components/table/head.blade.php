@blaze
@props(['sortable' => false, 'direction' => null])

@php
    $ariaSort = $sortable ? (['asc' => 'ascending', 'desc' => 'descending'][$direction] ?? 'none') : null;
    $headAttributes = ($sortable ? new \Illuminate\View\ComponentAttributeBag : $attributes)
        ->merge(['scope' => 'col', 'aria-sort' => $ariaSort, 'class' => 'h-9 px-3 text-left text-xs font-medium text-fg-3']);
@endphp

<th {{ $headAttributes }}>
    @if ($sortable)
        <button type="button" {{ $attributes->merge(['class' => 'group -mx-2 inline-flex items-center gap-1 rounded-md px-2 py-1 transition duration-120 ease-ob hover:bg-surface-3 hover:text-fg focus-ring max-sm:min-h-11']) }}>
            {{ $slot }}
            <x-ob::icon :name="$direction === 'desc' ? 'chevron-down' : 'chevron-up'" variant="micro" :class="'size-3 '.($direction === null ? 'opacity-0 group-hover:opacity-100' : '')" />
        </button>
    @else
        {{ $slot }}
    @endif
</th>
