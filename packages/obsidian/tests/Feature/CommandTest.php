<?php

it('renders an accessible multiselect combobox entangled with wire:model', function () {
    $this->blade('<x-ob::combobox label="Assignee" wire:model="assigneeIds" multiple :options="[[\'value\' => 1, \'label\' => \'Ada Lovelace\', \'description\' => \'Engineering\']]" />')
        ->assertSee('role="combobox"', false)
        ->assertSee('role="listbox"', false)
        ->assertSee('aria-multiselectable="true"', false)
        ->assertSee('aria-controls="ob-cb-', false)
        ->assertSee("\$wire.\$entangle('assigneeIds')", false)
        ->assertSee('Assignee')
        ->assertSee('Ada Lovelace')
        ->assertDontSee('wire:model="assigneeIds"', false);
});

it('leaves a single combobox unentangled and non-multiselectable, bound through x-model', function () {
    $this->blade('<x-ob::combobox x-model="picked" :options="[[\'value\' => 1, \'label\' => \'Ada\']]" />')
        ->assertSee('x-model="picked"', false)
        ->assertSee('x-modelable="value"', false)
        ->assertDontSee('$entangle', false)
        ->assertDontSee('aria-multiselectable', false);
});

it('moves the combobox search to the server when a search property is given', function () {
    $this->blade('<x-ob::combobox search="assigneeQuery" :options="[]" />')
        ->assertSee('wire:model.live.debounce.250ms="assigneeQuery"', false)
        ->assertSee('server: true', false);

    $this->blade('<x-ob::combobox :options="[]" />')
        ->assertDontSee('wire:model.live.debounce', false)
        ->assertSee('server: false', false);
});

it('forwards the palette wire:model to the query input only', function () {
    $html = (string) $this->blade('<x-ob::command-palette wire:model.live.debounce.150ms="query" :actions="[[\'label\' => \'New task\', \'href\' => \'/tasks/create\', \'icon\' => \'plus\']]" />');

    expect($html)->toMatch('/<input[^>]*wire:model\.live\.debounce\.150ms="query"/s')
        ->and(substr_count($html, 'wire:model.live.debounce.150ms="query"'))->toBe(1)
        ->and($html)->toContain('x-data="obCommandPalette(')
        ->and($html)->toContain('New task')
        ->and($html)->toContain('<svg');
});

it('renders a palette item as a navigable option', function () {
    $this->blade('<x-ob::command-palette.item href="/tasks/1" icon="check" meta="Task">Fix login</x-ob::command-palette.item>')
        ->assertSee('data-ob-command-item', false)
        ->assertSee('role="option"', false)
        ->assertSee('wire:navigate', false)
        ->assertSee('href="/tasks/1"', false)
        ->assertSee('Fix login')
        ->assertSee('Task');
});

it('labels a palette group', function () {
    $this->blade('<x-ob::command-palette.group heading="Tasks">Rows</x-ob::command-palette.group>')
        ->assertSee('role="group"', false)
        ->assertSee('aria-label="Tasks"', false)
        ->assertSee('Rows');
});
