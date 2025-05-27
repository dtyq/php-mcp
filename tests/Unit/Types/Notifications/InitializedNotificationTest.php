<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Notifications;

use Dtyq\PhpMcp\Types\Notifications\InitializedNotification;
use PHPUnit\Framework\TestCase;

/**
 * Test case for InitializedNotification class.
 * @internal
 */
class InitializedNotificationTest extends TestCase
{
    public function testConstructorWithoutMeta(): void
    {
        $notification = new InitializedNotification();

        $this->assertSame('notifications/initialized', $notification->getMethod());
        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    public function testConstructorWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $notification = new InitializedNotification($meta);

        $this->assertSame('notifications/initialized', $notification->getMethod());
        $this->assertTrue($notification->hasMeta());
        $this->assertSame($meta, $notification->getMeta());
    }

    public function testSetMeta(): void
    {
        $notification = new InitializedNotification();
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
        $notification = new InitializedNotification();

        $params = $notification->getParams();
        $this->assertNull($params);
    }

    public function testGetParamsWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $notification = new InitializedNotification($meta);

        $params = $notification->getParams();
        $this->assertIsArray($params);
        $this->assertSame($meta, $params['_meta']);
    }

    public function testToJsonRpcWithoutMeta(): void
    {
        $notification = new InitializedNotification();

        $jsonRpc = $notification->toJsonRpc();
        $this->assertSame('2.0', $jsonRpc['jsonrpc']);
        $this->assertSame('notifications/initialized', $jsonRpc['method']);
        $this->assertArrayNotHasKey('params', $jsonRpc);
    }

    public function testToJsonRpcWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $notification = new InitializedNotification($meta);

        $jsonRpc = $notification->toJsonRpc();
        $this->assertSame('2.0', $jsonRpc['jsonrpc']);
        $this->assertSame('notifications/initialized', $jsonRpc['method']);
        $this->assertArrayHasKey('params', $jsonRpc);
        $this->assertSame($meta, $jsonRpc['params']['_meta']);
    }

    public function testFromArrayWithoutMeta(): void
    {
        $data = ['params' => []];

        $notification = InitializedNotification::fromArray($data);

        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    public function testFromArrayWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $data = ['params' => ['_meta' => $meta]];

        $notification = InitializedNotification::fromArray($data);

        $this->assertTrue($notification->hasMeta());
        $this->assertSame($meta, $notification->getMeta());
    }

    public function testFromArrayWithoutParams(): void
    {
        $data = [];

        $notification = InitializedNotification::fromArray($data);

        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }
}
