@blaze

<div {{ $attributes->merge(['class' => 'rounded-xl border border-hairline bg-surface-1 overflow-clip']) }}>
    {{-- overflow-clip, not hidden/auto: those make scroll containers and pin the sticky thead to this wrapper instead of the page. --}}
    <div class="overflow-clip">
        <table class="w-full table-fixed text-sm max-sm:block">
            @isset($head)
                <thead class="glass shadow-none! sticky top-0 z-[1] max-sm:hidden"><tr>{{ $head }}</tr></thead>
            @endisset
            <tbody class="max-sm:block">{{ $slot }}</tbody>
        </table>
    </div>
</div>
