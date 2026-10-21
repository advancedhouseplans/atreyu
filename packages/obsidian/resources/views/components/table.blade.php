@blaze

<div {{ $attributes->merge(['class' => 'rounded-xl border border-hairline bg-surface-1 overflow-hidden']) }}>
    <div class="sm:overflow-x-auto">
        <table class="w-full text-sm max-sm:block">
            @isset($head)
                <thead class="glass sticky top-0 z-[1] max-sm:hidden"><tr>{{ $head }}</tr></thead>
            @endisset
            <tbody class="max-sm:block">{{ $slot }}</tbody>
        </table>
    </div>
</div>
