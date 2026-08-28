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
        $b->setRouteName('plans.show');
        $b->setAuth('web', 42, 'a@b');
        $b->addModel('App\\Models\\Plan', 1);
        $b->addQuery('select *', [str_repeat('x', 500)], 4.1, 'mysql');
        $b->addException(new RuntimeException('boom'));

        $arr = $b->toArray();
        $this->assertSame('id1', $arr['debug_id']);
        $this->assertSame('plans.show', $arr['request']['route_name']);
        $this->assertSame(42, $arr['auth']['user_id']);
        $this->assertSame('App\\Models\\Plan', $arr['models'][0]['class']);
        $this->assertSame(200, strlen($arr['queries'][0]['bindings'][0]));
        $this->assertSame('boom', $arr['exceptions'][0]['message']);
    }

    public function test_bounded(): void
    {
        config()->set('bug-companion.max_queries', 5);
        $b = new ContextBuffer('id1', 'GET', '/');
        for ($i = 0; $i < 20; $i++) {
            $b->addQuery("q{$i}", [], 1, 'mysql');
        }
        $this->assertCount(5, $b->toArray()['queries']);
    }
}
