<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Obsidian\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Shares a validation error bag with every view, the way ShareErrorsFromSession does.
 *
 * @param  array<string, list<string>>  $messages
 */
function withErrors(array $messages = ['email' => ['Enter an email address.']]): mixed
{
    view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag($messages)));

    return test();
}
