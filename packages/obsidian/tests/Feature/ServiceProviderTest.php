<?php

it('hides the showcase outside the local environment', function () {
    $this->get('/_obsidian')->assertNotFound();
});

it('serves the showcase locally', function () {
    app()->detectEnvironment(fn () => 'local');
    $this->withoutVite()->get('/_obsidian')->assertOk()->assertSee('Obsidian');
});

it('renders every component group on the showcase', function () {
    app()->detectEnvironment(fn () => 'local');
    $this->withoutVite();

    $this->get('/_obsidian')
        ->assertOk()
        ->assertSee('Stress')
        ->assertSee('data-ob-command-item', false)
        ->assertSee('wire:sort:item', false)
        ->assertSee('role="dialog"', false);
});
