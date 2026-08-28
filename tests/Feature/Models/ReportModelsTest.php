<?php

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportNote;
use App\Models\User;

it('creates a Report with array casts', function () {
    $r = Report::factory()->create([
        'laravel_context' => [['debug_id' => 'x', 'route_name' => 'home']],
        'client_events' => [['type' => 'error']],
        'network_log' => [['url' => '/x']],
    ]);
    expect($r->laravel_context)->toBeArray()->toHaveCount(1);
    expect($r->client_events[0]['type'])->toBe('error');
});

it('relates notes and attachments and assignee', function () {
    $u = User::factory()->create();
    $r = Report::factory()->for($u, 'assignee')->create();
    ReportNote::factory()->for($r)->for($u, 'author')->create(['body' => 'hi']);
    ReportAttachment::factory()->for($r)->create();

    expect($r->fresh()->notes)->toHaveCount(1)
        ->and($r->fresh()->attachments)->toHaveCount(1)
        ->and($r->fresh()->assignee->id)->toBe($u->id);
});
