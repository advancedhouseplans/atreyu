<?php

namespace BugCompanion\Tests\Feature;

use BugCompanion\Support\ContextBuffer;
use BugCompanion\Tests\TestCase;
use RuntimeException;

class ContextBufferTest extends TestCase
{
    public function test_captures_shape(): void
    {
        $b = new ContextBuffer('id1', 'GET', '/plans/1');
        $b->setRequestContext(['route_name' => 'plans.show']);
        $user = new class
        {
            public string $email = 'a@b';

            public string $name = 'Jamie';

            public function getAuthIdentifier(): int
            {
                return 42;
            }
        };
        $b->setUser('web', $user);
        $b->addModel('retrieved', 'App\\Models\\Plan', 1);
        $b->addQuery('select *', [str_repeat('x', 500)], 4.1, 'mysql');
        $b->addLog('warning', 'Slow request', ['password' => 'do-not-capture']);
        $b->addException(new RuntimeException('boom'));
        $b->finish(200);

        $arr = $b->toArray();
        $this->assertSame('id1', $arr['debug_id']);
        $this->assertSame('plans.show', $arr['request']['route_name']);
        $this->assertSame(42, $arr['user']['id']);
        $this->assertSame('App\\Models\\Plan', $arr['models'][0]['class']);
        $this->assertSame('[STRING 500 chars]', $arr['queries'][0]['bindings'][0]);
        $this->assertSame('boom', $arr['exceptions'][0]['message']);
        $this->assertSame('[REDACTED]', $arr['logs'][0]['context']['password']);
        $this->assertSame(200, $arr['response']['status']);
    }

    public function test_bounded(): void
    {
        config()->set('bug-companion.telemetry.max_queries', 5);
        $b = new ContextBuffer('id1', 'GET', '/');
        for ($i = 0; $i < 20; $i++) {
            $b->addQuery("q{$i}", [], 1, 'mysql');
        }
        $this->assertCount(5, $b->toArray()['queries']);
    }
}
