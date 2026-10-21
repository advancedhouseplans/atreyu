<?php

use Illuminate\View\ViewException;

it('applies variant and size classes', fn () => $this->blade('<x-ob::button variant="primary" size="sm">Save</x-ob::button>')->assertSee('bg-accent', false)->assertSee('h-8', false)->assertSee('type="button"', false));
it('renders a link for href and adds wire:navigate', fn () => $this->blade('<x-ob::button href="/reports" navigate>Reports</x-ob::button>')->assertSee('<a', false)->assertSee('href="/reports"', false)->assertSee('wire:navigate', false));
it('scopes the loading state to its own action', fn () => $this->blade('<x-ob::button wire:click="save(1)">Save</x-ob::button>')->assertSee('wire:target="save"', false)->assertSee('wire:loading.attr="data-loading"', false));
it('requires a label when icon-only', fn () => $this->blade('<x-ob::button icon="trash" />'))->throws(ViewException::class, 'requires a label');
it('labels icon-only buttons', fn () => $this->blade('<x-ob::button icon="trash" label="Delete" />')->assertSee('aria-label="Delete"', false)->assertSee('role="tooltip"', false));
it('renders a trailing icon without leaking the attribute', fn () => $this->blade('<x-ob::button icon:trailing="chevron-down">Menu</x-ob::button>')->assertSee('<svg', false)->assertDontSee('icon:trailing', false));
it('skips loading wiring when loading is false', fn () => $this->blade('<x-ob::button wire:click="save" :loading="false">Save</x-ob::button>')->assertDontSee('wire:target', false));
