<?php

it('embeds the server preference and clears legacy keys', function () {
    $this->blade('<x-ob::theme-script appearance="dark" accent="teal" />')
        ->assertSee("'dark'", false)->assertSee("'teal'", false)
        ->assertSee('ob-theme', false)->assertSee('mary-theme', false);
});

it('renders with no preference', function () {
    $this->blade('<x-ob::theme-script />')->assertSee('<script>', false);
});
