<?php

it('renders a badge with a dot', fn () => $this->blade('<x-ob::badge color="danger" dot>Late</x-ob::badge>')->assertSee('bg-danger-soft', false)->assertSee('size-1.5 rounded-full bg-danger', false)->assertSee('Late'));
it('renders avatar initials without an image', fn () => $this->blade('<x-ob::avatar initials="JH" />')->assertSee('JH')->assertDontSee('<img', false));
it('renders an avatar image when src is given', fn () => $this->blade('<x-ob::avatar src="/a.png" size="lg" />')->assertSee('<img', false)->assertSee('size-10', false));
it('overlaps stacked avatars', fn () => $this->blade('<x-ob::avatar.stack><x-ob::avatar initials="A" /></x-ob::avatar.stack>')->assertSee('-space-x-2', false)->assertSee('ring-canvas', false));
it('renders a tooltip', fn () => $this->blade('<x-ob::tooltip text="Hi"><button>x</button></x-ob::tooltip>')->assertSee('role="tooltip"', false)->assertSee('Hi'));
it('renders kbd, spinner and separators', function () {
    $this->blade('<x-ob::kbd>K</x-ob::kbd>')->assertSee('font-mono', false);
    $this->blade('<x-ob::spinner />')->assertSee('motion-safe:animate-spin', false);
    $this->blade('<x-ob::separator vertical />')->assertSee('aria-orientation="vertical"', false);
    $this->blade('<x-ob::separator label="or" />')->assertSee('or');
});
