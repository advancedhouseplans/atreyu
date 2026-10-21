<?php

it('renders an outline heroicon by default with the default size', function () {
    $html = (string) $this->blade('<x-ob::icon name="inbox" />');

    expect($html)->toContain('<svg')->toContain('size-5 shrink-0');
});

it('lets a caller size class replace the default size', function () {
    $html = (string) $this->blade('<x-ob::icon name="inbox" class="size-4" />');

    expect($html)->toContain('size-4')->toContain('shrink-0')->not->toContain('size-5');
});

it('treats width and height utilities as a caller size', function () {
    $html = (string) $this->blade('<x-ob::icon name="inbox" class="w-4 h-4" />');

    expect($html)->not->toContain('size-5');
});

it('marks the icon aria-hidden exactly once', function () {
    $html = (string) $this->blade('<x-ob::icon name="inbox" />');

    expect(substr_count($html, 'aria-hidden="true"'))->toBe(1);
});

it('forwards other attributes to the svg', function () {
    $html = (string) $this->blade('<x-ob::icon name="inbox" data-test="x" />');

    expect($html)->toContain('data-test="x"');
});

it('renders the solid variant', function () {
    expect(trim((string) $this->blade('<x-ob::icon name="inbox" variant="solid" />')))
        ->toBe(trim((string) $this->blade('@svg("heroicon-s-inbox", "size-5 shrink-0")')));
});
