<?php

it('shows the error for its wire:model name', fn () => withErrors()->blade('<x-ob::input label="Email" wire:model="email" />')->assertSee('Enter an email address.')->assertSee('<div data-invalid', false)->assertSee('role="alert"', false));
it('shows the hint when there is no error', fn () => $this->blade('<x-ob::input label="Email" hint="We never share it." wire:model="email" />')->assertSee('We never share it.')->assertDontSee('<div data-invalid', false)->assertDontSee('role="alert"', false));
it('shows the error for an explicit name', fn () => withErrors()->blade('<x-ob::select name="email" :options="[]" />')->assertSee('Enter an email address.'));
it('forwards wire:model to the inner control', fn () => $this->blade('<x-ob::input wire:model.live="q" class="w-64" />')->assertSeeInOrder(['class="w-64', '<input', 'wire:model.live="q"'], false));
it('links the label to the control', fn () => $this->blade('<x-ob::textarea label="Notes" wire:model="notes" />')->assertSee('for="notes"', false)->assertSee('id="notes"', false));
it('renders select options', fn () => $this->blade('<x-ob::select :options="[[\'value\' => \'new\', \'label\' => \'New\']]" placeholder="Any status" />')->assertSee('<option value="">Any status</option>', false)->assertSee('<option value="new">New</option>', false));
it('renders select options from the slot', fn () => $this->blade('<x-ob::select><option value="a">A</option></x-ob::select>')->assertSee('<option value="a">A</option>', false));
it('marks switches with the switch role', fn () => $this->blade('<x-ob::switch label="Notify" wire:model="notify" />')->assertSee('role="switch"', false)->assertDontSee('aria-checked', false)->assertDontSee('x-data', false));
it('renders a segmented control as a radiogroup', fn () => $this->blade('<x-ob::segmented name="mode" :options="[[\'value\' => \'dark\', \'label\' => \'Dark\']]" />')->assertSee('role="radiogroup"', false)->assertSee('value="dark"', false)->assertSee('transition-[left,width] duration-200 ease-ob', false));
it('renders a checkbox with its label', fn () => $this->blade('<x-ob::checkbox label="Remember me" wire:model="remember" />')->assertSee('type="checkbox"', false)->assertSee('Remember me')->assertSee('wire:model="remember"', false));
it('renders radio options sharing one name', fn () => $this->blade('<x-ob::radio-group label="Plan" wire:model="plan" :options="[[\'value\' => \'a\', \'label\' => \'Alpha\'], [\'value\' => \'b\', \'label\' => \'Beta\']]" />')->assertSee('role="radiogroup"', false)->assertSee('Alpha')->assertSee('name="plan"', false)->assertSee('value="b"', false));
it('renders a clearable input with leading icon, prefix and suffix', fn () => $this->blade('<x-ob::input icon="magnifying-glass" clearable prefix="$" suffix="USD" wire:model="q" />')->assertSee('Clear', false)->assertSee('USD')->assertSee('$'));
it('toggles password visibility', fn () => $this->blade('<x-ob::password label="Password" wire:model="password" />')->assertSee('Show password', false)->assertSee('Hide password', false)->assertSee('type="password"', false));
it('forwards the model to the segmented root and binds radios to it', fn () => $this->blade('<x-ob::segmented name="mode" wire:model="mode" :options="[[\'value\' => \'dark\', \'label\' => \'Dark\']]" />')->assertSeeInOrder(['role="radiogroup"', 'wire:model="mode"', '<input', 'x-model="value"'], false)->assertSee('x-modelable="value"', false)->assertSee('x-effect=', false));
it('describes the control only when there is a hint or an error', function () {
    $this->blade('<x-ob::input wire:model="email" />')->assertDontSee('aria-describedby', false);
    $this->blade('<x-ob::input hint="Work address" wire:model="email" />')->assertSee('aria-describedby="email-desc"', false)->assertSee('id="email-desc"', false);
    withErrors()->blade('<x-ob::input wire:model="email" />')->assertSee('aria-describedby="email-desc"', false)->assertSee('id="email-desc"', false);
});
it('marks the control invalid only when its key has an error', function () {
    $this->blade('<x-ob::input wire:model="email" />')->assertDontSee('aria-invalid', false);
    withErrors()->blade('<x-ob::input wire:model="name" />')->assertDontSee('aria-invalid', false);
    withErrors()->blade('<x-ob::input wire:model="email" />')->assertSee('aria-invalid="true"', false);
});
it('shows the clear button from the placeholder state instead of JS state', fn () => $this->blade('<x-ob::input clearable wire:model="q" />')->assertSee('placeholder=" "', false)->assertSee('peer-not-placeholder-shown:inline-flex', false)->assertDontSee('filled', false));
it('only measures an auto-growing textarea while it is visible', fn () => $this->blade('<x-ob::textarea wire:model="notes" />')->assertSee('scrollHeight &gt; 0', false)->assertSee('x-intersect=', false));
it('keeps 44px touch targets below sm', function () {
    $this->blade('<x-ob::checkbox label="A" wire:model="a" />')->assertSee('max-sm:min-h-11', false);
    $this->blade('<x-ob::switch label="A" wire:model="a" />')->assertSee('max-sm:min-h-11', false);
    $this->blade('<x-ob::radio-group :options="[[\'value\' => \'a\', \'label\' => \'A\']]" wire:model="a" />')->assertSee('max-sm:min-h-11', false);
    $this->blade('<x-ob::segmented name="m" :options="[[\'value\' => \'a\', \'label\' => \'A\']]" />')->assertSee('max-sm:h-11', false);
    $this->blade('<x-ob::input clearable wire:model="q" />')->assertSee('max-sm:size-11', false);
    $this->blade('<x-ob::password wire:model="p" />')->assertSee('max-sm:size-11', false);
});
it('links the label to an x-model-only control', function (string $component) {
    $html = (string) $this->blade("<x-ob::{$component} label=\"Notes\" x-model=\"notes\" />");

    preg_match('/<label[^>]*\sfor="([^"]+)"/', $html, $label);
    preg_match('/<(?:input|select|textarea)\b.*?\sid="([^"]+)"/s', $html, $control);

    expect($label[1] ?? null)->not->toBeEmpty()->toBe($control[1] ?? null);
})->with(['input', 'select', 'textarea']);
