{{--
    Application frame: sidebar from lg up, floating dock below lg, content in between.

    Overlay constraint: modals and sheets are not teleported, so nothing between this shell and page
    content (including `main`) may use transform, filter or backdrop-filter, or position:fixed overlays
    inside page content would anchor to that ancestor instead of the viewport. The sticky glass page-header
    bar is fine because it is a sibling of content, never an ancestor of a modal.
--}}
<div
    x-data="{ collapsed: $persist(false).as('ob-sidebar-collapsed') }"
    {{ $attributes->merge(['class' => 'min-h-dvh bg-canvas text-fg lg:grid lg:grid-cols-[auto_1fr]']) }}
>
    @isset($sidebar)
        <div class="sticky top-0 z-20 hidden h-dvh lg:block">{{ $sidebar }}</div>
    @endisset

    <main class="min-w-0 pb-[calc(6rem+env(safe-area-inset-bottom))] lg:pb-0">
        {{ $slot }}
    </main>

    @isset($dock)
        <div class="lg:hidden">{{ $dock }}</div>
    @endisset
</div>
