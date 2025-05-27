<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Tests\Unit\Types\Notifications;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Notifications\CancelledNotification;
use PHPUnit\Framework\TestCase;

/**
 * Test case for CancelledNotification class.
 * @internal
 */
class CancelledNotificationTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $requestId = 'request_123';
        $reason = 'User cancelled';
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];

        $notification = new CancelledNotification($requestId, $reason, $meta);

        $this->assertSame('notifications/cancelled', $notification->getMethod());
        $this->assertSame($requestId, $notification->getRequestId());
        $this->assertSame($reason, $notification->getReason());
        $this->assertTrue($notification->hasMeta());
        $this->assertSame($meta, $notification->getMeta());
    }

    public function testConstructorWithMinimalData(): void
    {
        $requestId = 'request_123';

        $notification = new CancelledNotification($requestId);

        $this->assertSame($requestId, $notification->getRequestId());
        $this->assertNull($notification->getReason());
        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    public function testSetRequestId(): void
    {
        $notification = new CancelledNotification('initial');

        $notification->setRequestId('updated_request');
        $this->assertSame('updated_request', $notification->getRequestId());

        $notification->setRequestId(12345);
        $this->assertSame(12345, $notification->getRequestId());
    }

    public function testSetRequestIdWithInvalidType(): void
    {
        $notification = new CancelledNotification('request');

        $this->expectException(ValidationError::class);
        $notification->setRequestId(12.34);
    }

    public function testSetReason(): void
    {
        $notification = new CancelledNotification('request');

        $notification->setReason('New reason');
        $this->assertSame('New reason', $notification->getReason());

        $notification->setReason(null);
        $this->assertNull($notification->getReason());
    }

    public function testSetMeta(): void
    {
        $notification = new CancelledNotification('request');
        $meta = ['key' => 'value'];

        $notification->setMeta($meta);
        $this->assertTrue($notification->hasMeta());
        $this->assertSame($meta, $notification->getMeta());

        $notification->setMeta(null);
        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    public function testGetParams(): void
    {
        $requestId = 'request_123';
        $reason = 'User cancelled';

        $notification = new CancelledNotification($requestId, $reason);

        $params = $notification->getParams();
        $this->assertIsArray($params);
        $this->assertSame($requestId, $params['requestId']);
        $this->assertSame($reason, $params['reason']);
        $this->assertArrayNotHasKey('_meta', $params);
    }

    public function testGetParamsWithoutReason(): void
    {
        $requestId = 'request_123';
        $notification = new CancelledNotification($requestId);

        $params = $notification->getParams();
        $this->assertIsArray($params);
        $this->assertSame($requestId, $params['requestId']);
        $this->assertArrayNotHasKey('reason', $params);
    }

    public function testGetParamsWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $notification = new CancelledNotification('request', null, $meta);

        $params = $notification->getParams();
        $this->assertArrayHasKey('_meta', $params);
        $this->assertSame($meta, $params['_meta']);
    }

    public function testToJsonRpc(): void
    {
        $requestId = 'request_123';
        $reason = 'User cancelled';

        $notification = new CancelledNotification($requestId, $reason);

        $jsonRpc = $notification->toJsonRpc();
        $this->assertSame('2.0', $jsonRpc['jsonrpc']);
        $this->assertSame('notifications/cancelled', $jsonRpc['method']);
        $this->assertIsArray($jsonRpc['params']);
        $this->assertSame($requestId, $jsonRpc['params']['requestId']);
        $this->assertSame($reason, $jsonRpc['params']['reason']);
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'params' => [
                'requestId' => 'request_123',
                'reason' => 'User cancelled',
                '_meta' => ['timestamp' => '2025-01-01T00:00:00Z'],
            ],
        ];

        $notification = CancelledNotification::fromArray($data);

        $this->assertSame('request_123', $notification->getRequestId());
        $this->assertSame('User cancelled', $notification->getReason());
        $this->assertTrue($notification->hasMeta());
        $this->assertSame(['timestamp' => '2025-01-01T00:00:00Z'], $notification->getMeta());
    }

    public function testFromArrayWithoutReason(): void
    {
        $data = [
            'params' => [
                'requestId' => 'request_123',
            ],
        ];

        $notification = CancelledNotification::fromArray($data);

        $this->assertSame('request_123', $notification->getRequestId());
        $this->assertNull($notification->getReason());
        $this->assertFalse($notification->hasMeta());
    }

    public function testFromArrayMissingRequestId(): void
    {
        $data = [
            'params' => [
                'reason' => 'User cancelled',
            ],
        ];

        $this->expectException(ValidationError::class);
        CancelledNotification::fromArray($data);
    }
}
