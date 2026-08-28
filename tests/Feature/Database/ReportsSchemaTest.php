<?php

use Illuminate\Support\Facades\Schema;

it('has reports table with all documented columns', function () {
    expect(Schema::hasTable('reports'))->toBeTrue();
    $cols = ['source_slug', 'environment', 'reporter_user_id', 'reporter_email', 'reporter_name',
        'type', 'severity', 'status', 'assignee_id', 'title', 'description',
        'url', 'referrer', 'app_version', 'user_agent', 'browser', 'browser_version',
        'os', 'os_version', 'device', 'screen_wh', 'viewport_wh', 'dpr',
        'timezone', 'locale', 'laravel_context', 'client_events', 'network_log'];
    foreach ($cols as $c) {
        expect(Schema::hasColumn('reports', $c))->toBeTrue("missing: {$c}");
    }
});

it('has report_notes and report_attachments tables', function () {
    expect(Schema::hasTable('report_notes'))->toBeTrue();
    expect(Schema::hasTable('report_attachments'))->toBeTrue();
    foreach (['report_id', 'user_id', 'body'] as $c) {
        expect(Schema::hasColumn('report_notes', $c))->toBeTrue();
    }
    foreach (['report_id', 'disk', 'path', 'mime', 'size', 'kind'] as $c) {
        expect(Schema::hasColumn('report_attachments', $c))->toBeTrue();
    }
});
