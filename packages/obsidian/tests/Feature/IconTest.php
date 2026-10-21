<?php

it('renders an outline heroicon by default', function () {
    $this->blade('<x-ob::icon name="inbox" class="size-4" />')
        ->assertSee('<svg', false)
        ->assertSee('aria-hidden="true"', false)
        ->assertSee('size-4', false);
});

it('renders the solid variant', function () {
    expect((string) $this->blade('<x-ob::icon name="inbox" variant="solid" />'))
        ->toBe((string) $this->blade('@svg("heroicon-s-inbox", "size-5 shrink-0", ["aria-hidden" => "true"])'));
});
