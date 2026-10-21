{{-- Floating tab bar below lg; slides away on scroll down and returns on scroll up. Not a transform ancestor of page content. --}}
<nav
    aria-label="Primary"
    x-data="obDock"
    x-on:scroll.window.passive="onScroll()"
    x-bind:class="visible ? '' : 'translate-y-[150%]'"
    x-bind:data-ob-hidden="visible ? null : ''"
    data-ob-motion
    {{ $attributes->merge(['class' => 'glass fixed inset-x-3.5 bottom-[calc(.875rem+env(safe-area-inset-bottom))] z-30 flex h-14 items-center justify-around rounded-[20px] px-2 transition-transform duration-320 ease-ob']) }}
>
    {{ $slot }}
</nav>
