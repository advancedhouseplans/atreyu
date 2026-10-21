<?php

it('hides the showcase outside the local environment', function () {
    $this->get('/_obsidian')->assertNotFound();
});

it('serves the showcase locally', function () {
    app()->detectEnvironment(fn () => 'local');
    $this->get('/_obsidian')->assertOk()->assertSee('Obsidian');
});
