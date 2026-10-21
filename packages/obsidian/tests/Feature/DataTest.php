<?php

it('clips the table without creating a scroll container so the head sticks to the page', function () {
    $html = (string) $this->blade('<x-ob::table><x-ob::table.row><x-ob::table.cell>A</x-ob::table.cell></x-ob::table.row></x-ob::table>');

    expect($html)->toContain('overflow-clip')
        ->toContain('table-fixed')
        ->not->toContain('overflow-hidden')
        ->not->toContain('overflow-x-auto');
});

it('renders a table with a sticky, shadowless glass head', function () {
    $this->blade('<x-ob::table><x-ob::table.row><x-ob::table.cell>A</x-ob::table.cell></x-ob::table.row><x-slot:head><x-ob::table.head>Name</x-ob::table.head></x-slot:head></x-ob::table>')
        ->assertSee('<thead class="glass shadow-none! sticky top-0', false)
        ->assertSee('scope="col"', false)
        ->assertSee('Name');
});

it('marks a sortable head with aria-sort and forwards its attributes to the button', function () {
    $this->blade('<x-ob::table.head sortable direction="desc" wire:click="sort(\'name\')">Name</x-ob::table.head>')
        ->assertSee('aria-sort="descending"', false)
        ->assertSee('<button type="button"', false)
        ->assertSee('wire:click="sort(\'name\')"', false);

    $this->blade('<x-ob::table.head sortable>Name</x-ob::table.head>')
        ->assertSee('aria-sort="none"', false);
});

it('stretches a link over the row from the first cell only', function () {
    $html = (string) $this->blade('<x-ob::table.row href="/r/1" wire:key="r1"><x-ob::table.cell mobile="primary">First</x-ob::table.cell><x-ob::table.cell>Second</x-ob::table.cell></x-ob::table.row>');

    expect($html)->toContain('href="/r/1"')
        ->toContain('wire:navigate')
        ->toContain('after:absolute after:inset-0')
        ->toContain('wire:key="r1"')
        ->toContain('>First</a>')
        ->and(substr_count($html, '<a '))->toBe(1);
});

it('wraps first-cell markup inside the link', function () {
    $html = (string) $this->blade('<x-ob::table.row href="/r/1"><x-ob::table.cell mobile="primary"><span class="x">Title</span></x-ob::table.cell><x-ob::table.cell>B</x-ob::table.cell></x-ob::table.row>');

    expect($html)->toContain('wire:navigate class="after:absolute after:inset-0 focus-ring"><span class="x">Title</span></a></td>')
        ->and(substr_count($html, '<a '))->toBe(1);
});

it('renders a plain row without a link', function () {
    $this->blade('<x-ob::table.row><x-ob::table.cell>A</x-ob::table.cell></x-ob::table.row>')
        ->assertDontSee('<a ', false);
});

it('hides, promotes or demotes a cell below sm', function () {
    $this->blade('<x-ob::table.cell mobile="hide">x</x-ob::table.cell>')->assertSee('max-sm:hidden', false);
    $this->blade('<x-ob::table.cell mobile="primary">x</x-ob::table.cell>')->assertSee('max-sm:col-span-full', false);
    $this->blade('<x-ob::table.cell>x</x-ob::table.cell>')->assertSee('max-sm:text-fg-3', false);
});

it('lays out a property row with an optional icon', function () {
    $this->blade('<x-ob::property label="Assignee" icon="user">Ada</x-ob::property>')
        ->assertSee('grid-cols-[8rem_1fr]', false)
        ->assertSee('Assignee')
        ->assertSee('<svg', false)
        ->assertSee('Ada');
});

it('wires a kanban column into wire:sort', function () {
    $this->blade('<x-ob::kanban.column id="todo" title="To do" :count="3" sort="moveTask" group="tasks"><x-ob::kanban.card id="7">Fix it</x-ob::kanban.card></x-ob::kanban.column>')
        ->assertSee('wire:sort="moveTask"', false)
        ->assertSee('wire:sort:group="tasks"', false)
        ->assertSee('wire:sort:group-id="todo"', false)
        ->assertSee('To do')
        ->assertSeeInOrder(['To do', '3'])
        ->assertDontSee('Drop here');
});

it('renders the empty slot only when a column has no cards', function () {
    $empty = '<x-slot:empty>Drop here</x-slot:empty>';

    $this->blade('<x-ob::kanban.column id="todo" title="To do" sort="moveTask" group="tasks">'.$empty.'</x-ob::kanban.column>')
        ->assertSee('Drop here');

    $this->blade('<x-ob::kanban.column id="todo" title="To do" sort="moveTask" group="tasks"><x-ob::kanban.card id="1">A</x-ob::kanban.card>'.$empty.'</x-ob::kanban.column>')
        ->assertDontSee('Drop here');
});

it('renders a kanban card with a handle and ignored content', function () {
    $this->blade('<x-ob::kanban.card id="7" wire:key="card-7">Fix it</x-ob::kanban.card>')
        ->assertSee('wire:sort:item="7"', false)
        ->assertSee('wire:sort:handle', false)
        ->assertSee('aria-label="Move"', false)
        ->assertSee('wire:sort:ignore', false)
        ->assertSee('wire:key="card-7"', false)
        ->assertSee('sortable-chosen', false)
        ->assertSee('sortable-ghost', false);
});

it('uses a custom label for the kanban handle', function () {
    $this->blade('<x-ob::kanban.card id="7" label="Move Fix login">Fix</x-ob::kanban.card>')
        ->assertSee('aria-label="Move Fix login"', false);
});

it('renders an inline edit button bound to a Livewire action', function () {
    $this->blade('<x-ob::inline-edit value="Old" action="updateTitle" name="title" label="Title" />')
        ->assertSee('aria-label="Edit Title"', false)
        ->assertSee('updateTitle')
        ->assertSee('x-data="obInlineEdit(', false)
        ->assertSee('Old');
});

it('shows the option label and placeholder in a select inline edit', function () {
    $options = "[['value' => 1, 'label' => 'Ada'], ['value' => 2, 'label' => 'Grace']]";

    $this->blade("<x-ob::inline-edit :value=\"2\" action=\"updateAssignee\" name=\"assignee\" type=\"select\" :options=\"{$options}\" label=\"Assignee\" placeholder=\"Unassigned\" />")
        ->assertSee('<select', false)
        ->assertSee('<option value="">Unassigned</option>', false)
        ->assertSee('>Grace</span>', false);

    $this->blade("<x-ob::inline-edit action=\"updateAssignee\" name=\"assignee\" type=\"select\" :options=\"{$options}\" label=\"Assignee\" placeholder=\"Unassigned\" />")
        ->assertSee('>Unassigned</span>', false);
});
