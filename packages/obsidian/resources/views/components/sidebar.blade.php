{{-- Collapsible navigation rail; reads and writes `collapsed` from the surrounding <x-ob::app-shell>. --}}
<aside
    x-bind:class="{ 'w-16!': collapsed }"
    {{ $attributes->merge(['class' => 'flex h-full w-60 flex-col border-r border-hairline bg-canvas transition-[width] duration-200 ease-ob']) }}
>
    <div class="flex h-14 shrink-0 items-center gap-2 px-3">
        <div x-show="!collapsed" class="min-w-0 flex-1 truncate">{{ $brand ?? '' }}</div>
        <div x-show="!collapsed" class="shrink-0">
            <x-ob::button variant="ghost" size="sm" icon="chevron-double-left" label="Collapse sidebar" x-on:click="collapsed = true" />
        </div>
        <div x-show="collapsed" x-cloak class="mx-auto shrink-0">
            <x-ob::button variant="ghost" size="sm" icon="chevron-double-right" label="Expand sidebar" x-on:click="collapsed = false" />
        </div>
    </div>

    <nav aria-label="Primary" class="flex-1 space-y-4 px-2 py-2">
        {{ $slot }}
    </nav>

    @isset($footer)
        <div class="shrink-0 border-t border-hairline p-2">{{ $footer }}</div>
    @endisset
</aside>
