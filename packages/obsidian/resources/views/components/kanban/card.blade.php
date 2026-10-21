@blaze
@props(['id', 'label' => 'Move'])

<article wire:sort:item="{{ $id }}" {{ $attributes->merge(['class' => 'rounded-lg bg-surface-2 border border-hairline p-3 transition duration-120 ease-ob [&.sortable-chosen]:shadow-xl [&.sortable-chosen]:scale-[1.02] [&.sortable-ghost]:opacity-30 [&.sortable-ghost]:outline-2 [&.sortable-ghost]:outline-dashed [&.sortable-ghost]:outline-accent-soft']) }}>
    <div class="flex items-start gap-2">
        <div wire:sort:ignore class="min-w-0 flex-1 wrap-break-word">{{ $slot }}</div>
        <x-ob::button variant="ghost" size="sm" icon="bars-3" :label="$label" wire:sort:handle class="-m-1 shrink-0 cursor-grab touch-none active:cursor-grabbing max-sm:size-11" />
    </div>
</article>
