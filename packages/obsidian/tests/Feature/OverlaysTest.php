<?php

it('renders an accessible modal that traps focus', function () {
    $this->blade('<x-ob::modal name="confirm" title="Delete project" description="This cannot be undone." size="lg">Body<x-slot:footer>Footer</x-slot:footer></x-ob::modal>')
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('aria-labelledby="ob-modal-', false)
        ->assertSee('x-trap.inert.noscroll', false)
        ->assertSee('data-ob-layer="modal"', false)
        ->assertSee('sm:max-w-2xl', false)
        ->assertSee('Delete project')
        ->assertSee('Footer')
        ->assertSee("name: 'confirm'", false)
        ->assertDontSee('$entangle', false);
});

it('entangles the modal open state with the wire:model property', function () {
    $this->blade('<x-ob::modal wire:model="showCreate" title="New">Body</x-ob::modal>')
        ->assertSee("\$wire.\$entangle('showCreate')", false)
        ->assertDontSee('wire:model', false);
});

it('entangles live only when wire:model.live is used, on modal and sheet', function () {
    $this->blade('<x-ob::modal wire:model.live="showCreate">Body</x-ob::modal>')->assertSee("\$wire.\$entangle('showCreate', true)", false);
    $this->blade('<x-ob::modal wire:model="showCreate">Body</x-ob::modal>')->assertDontSee("'showCreate', true", false);
    $this->blade('<x-ob::sheet wire:model.live="showMenu">Body</x-ob::sheet>')->assertSee("\$wire.\$entangle('showMenu', true)", false);
    $this->blade('<x-ob::sheet wire:model="showMenu">Body</x-ob::sheet>')->assertDontSee("'showMenu', true", false);
});

it('renders a bottom sheet with a drag handle', function () {
    $this->blade('<x-ob::sheet name="filters" title="Filters">Body</x-ob::sheet>')
        ->assertSee('data-ob-layer="sheet"', false)
        ->assertSee('rounded-t-[20px]', false)
        ->assertSee('startDrag($event)', false)
        ->assertSee('safe-area-inset-bottom', false);
});

it('renders a full-height right sheet without a drag handle', function () {
    $this->blade('<x-ob::sheet side="right" wire:model="showMenu">Body</x-ob::sheet>')
        ->assertSee('inset-y-0 right-0', false)
        ->assertSee("\$wire.\$entangle('showMenu')", false)
        ->assertDontSee('startDrag', false);
});

it('renders a dropdown menu with a trigger and items', function () {
    $this->blade('<x-ob::dropdown position="top-start"><x-slot:trigger><button>Actions</button></x-slot:trigger><x-ob::menu.item icon="pencil" shortcut="E">Edit</x-ob::menu.item></x-ob::dropdown>')
        ->assertSee('x-data="obDropdown"', false)
        ->assertSee('x-anchor.top-start.offset.6.fixed', false)
        ->assertSee('role="menu"', false)
        ->assertSee('data-ob-trigger', false)
        ->assertSee('Actions')
        ->assertSee('Edit');
});

it('renders menu items as buttons or links', function () {
    $this->blade('<x-ob::menu.item variant="danger">Delete</x-ob::menu.item>')
        ->assertSee('role="menuitem"', false)
        ->assertSee('text-danger-fg', false)
        ->assertSee('max-sm:h-11', false)
        ->assertSee('<button', false);

    $this->blade('<x-ob::menu.item href="/p" navigate>Open</x-ob::menu.item>')
        ->assertSee('<a ', false)
        ->assertSee('href="/p"', false)
        ->assertSee('wire:navigate', false);
});

it('renders menu groups and separators', function () {
    $this->blade('<x-ob::menu.group label="Project"><x-ob::menu.item>Rename</x-ob::menu.item></x-ob::menu.group><x-ob::menu.separator />')
        ->assertSee('role="group"', false)
        ->assertSee('Project')
        ->assertSee('role="separator"', false);
});

it('renders a polite notifications region', function () {
    $this->blade('<x-ob::toasts />')
        ->assertSee('aria-live="polite"', false)
        ->assertSee('role="region"', false)
        ->assertSee('aria-label="Notifications"', false)
        ->assertSee('x-on:toast.window', false);
});

it('closes the menu through a delegated handler so item click handlers survive', function () {
    $html = (string) $this->blade('<x-ob::dropdown><x-slot:trigger><button>Go</button></x-slot:trigger><x-ob::menu.item x-on:click="$dispatch(\'modal-open\', {name: \'x\'})">Open</x-ob::menu.item></x-ob::dropdown>');

    expect($html)
        ->toContain('x-on:click="onMenuClick($event)"')
        ->toContain('x-on:click="$dispatch(')
        ->not->toContain('x-on:click="close()" role="menuitem"');
    expect(strpos($html, 'onMenuClick($event)'))->toBeLessThan(strpos($html, 'role="menuitem"'));
});

it('renders the dropdown slot exactly once', function () {
    $html = (string) $this->blade('<x-ob::dropdown><x-slot:trigger><button>Go</button></x-slot:trigger><x-ob::menu.item>UniqueItemLabel</x-ob::menu.item></x-ob::dropdown>');

    expect(substr_count($html, 'UniqueItemLabel'))->toBe(1)
        ->and(substr_count($html, 'role="menu"'))->toBe(1);
});

it('gives untitled dialogs an accessible name', function () {
    $this->blade('<x-ob::modal label="Quick add">Body</x-ob::modal>')
        ->assertSee('aria-label="Quick add"', false)
        ->assertDontSee('aria-labelledby', false);

    $this->blade('<x-ob::sheet aria-label="Filters">Body</x-ob::sheet>')
        ->assertSee('aria-label="Filters"', false)
        ->assertDontSee('aria-labelledby', false);
});
