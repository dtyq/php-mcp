<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Notifications;

use Dtyq\PhpMcp\Types\Notifications\ToolListChangedNotification;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ToolListChangedNotification class.
 * @internal
 */
class ToolListChangedNotificationTest extends TestCase
{
    public function testConstructorWithoutMeta(): void
    {
        $notification = new ToolListChangedNotification();

        $this->assertSame('notifications/tools/list_changed', $notification->getMethod());
        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    public function testConstructorWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $notification = new ToolListChangedNotification($meta);

        $this->assertSame('notifications/tools/list_changed', $notification->getMethod());
        $this->assertTrue($notification->hasMeta());
        $this->assertSame($meta, $notification->getMeta());
    }

    public function testSetMeta(): void
    {
        $notification = new ToolListChangedNotification();
        $meta = ['key' => 'value'];

        $notification->setMeta($meta);
        $this->assertTrue($notification->hasMeta());
        $this->assertSame($meta, $notification->getMeta());

        $notification->setMeta(null);
        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    public function testGetParamsWithoutMeta(): void
    {
        $notification = new ToolListChangedNotification();

        $params = $notification->getParams();
        $this->assertNull($params);
    }

    public function testGetParamsWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $notification = new ToolListChangedNotification($meta);

        $params = $notification->getParams();
        $this->assertIsArray($params);
        $this->assertSame($meta, $params['_meta']);
    }

    public function testToJsonRpcWithoutMeta(): void
    {
        $notification = new ToolListChangedNotification();

        $jsonRpc = $notification->toJsonRpc();
        $this->assertSame('2.0', $jsonRpc['jsonrpc']);
        $this->assertSame('notifications/tools/list_changed', $jsonRpc['method']);
        $this->assertArrayNotHasKey('params', $jsonRpc);
    }

    public function testToJsonRpcWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $notification = new ToolListChangedNotification($meta);

        $jsonRpc = $notification->toJsonRpc();
        $this->assertSame('2.0', $jsonRpc['jsonrpc']);
        $this->assertSame('notifications/tools/list_changed', $jsonRpc['method']);
        $this->assertArrayHasKey('params', $jsonRpc);
        $this->assertSame($meta, $jsonRpc['params']['_meta']);
    }

    public function testFromArrayWithoutMeta(): void
    {
        $data = ['params' => []];

        $notification = ToolListChangedNotification::fromArray($data);

        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    public function testFromArrayWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $data = ['params' => ['_meta' => $meta]];

        $notification = ToolListChangedNotification::fromArray($data);

        $this->assertTrue($notification->hasMeta());
        $this->assertSame($meta, $notification->getMeta());
    }

    public function testFromArrayWithoutParams(): void
    {
        $data = [];

        $notification = ToolListChangedNotification::fromArray($data);

        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }
}
