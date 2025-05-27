<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Notifications;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Notifications\ProgressNotification;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ProgressNotification class.
 * @internal
 */
class ProgressNotificationTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $progressToken = 'token_123';
        $progress = 50;
        $total = 100;
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];

        $notification = new ProgressNotification($progressToken, $progress, $total, $meta);

        $this->assertSame('notifications/progress', $notification->getMethod());
        $this->assertSame($progressToken, $notification->getProgressToken());
        $this->assertSame($progress, $notification->getProgress());
        $this->assertSame($total, $notification->getTotal());
        $this->assertTrue($notification->hasMeta());
        $this->assertSame($meta, $notification->getMeta());
    }

    public function testConstructorWithMinimalData(): void
    {
        $progressToken = 'token_123';
        $progress = 25;

        $notification = new ProgressNotification($progressToken, $progress);

        $this->assertSame($progressToken, $notification->getProgressToken());
        $this->assertSame($progress, $notification->getProgress());
        $this->assertNull($notification->getTotal());
        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    public function testSetProgressToken(): void
    {
        $notification = new ProgressNotification('initial', 0);

        $notification->setProgressToken('updated_token');
        $this->assertSame('updated_token', $notification->getProgressToken());

        $notification->setProgressToken(12345);
        $this->assertSame(12345, $notification->getProgressToken());
    }

    public function testSetProgressTokenWithInvalidType(): void
    {
        $notification = new ProgressNotification('token', 0);

        $this->expectException(ValidationError::class);
        $notification->setProgressToken(12.34);
    }

    public function testSetProgress(): void
    {
        $notification = new ProgressNotification('token', 0);

        $notification->setProgress(75);
        $this->assertSame(75, $notification->getProgress());
    }

    public function testSetProgressWithNegativeValue(): void
    {
        $notification = new ProgressNotification('token', 0);

        $this->expectException(ValidationError::class);
        $notification->setProgress(-1);
    }

    public function testSetTotal(): void
    {
        $notification = new ProgressNotification('token', 0);

        $notification->setTotal(200);
        $this->assertSame(200, $notification->getTotal());

        $notification->setTotal(null);
        $this->assertNull($notification->getTotal());
    }

    public function testSetTotalWithNegativeValue(): void
    {
        $notification = new ProgressNotification('token', 0);

        $this->expectException(ValidationError::class);
        $notification->setTotal(-1);
    }

    public function testSetMeta(): void
    {
        $notification = new ProgressNotification('token', 0);
        $meta = ['key' => 'value'];

        $notification->setMeta($meta);
        $this->assertTrue($notification->hasMeta());
        $this->assertSame($meta, $notification->getMeta());

        $notification->setMeta(null);
        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    public function testGetProgressPercentage(): void
    {
        $notification = new ProgressNotification('token', 25, 100);
        $this->assertSame(25.0, $notification->getProgressPercentage());

        $notification = new ProgressNotification('token', 150, 100);
        $this->assertSame(100.0, $notification->getProgressPercentage());

        $notification = new ProgressNotification('token', 25);
        $this->assertNull($notification->getProgressPercentage());

        $notification = new ProgressNotification('token', 25, 0);
        $this->assertNull($notification->getProgressPercentage());
    }

    public function testIsComplete(): void
    {
        $notification = new ProgressNotification('token', 100, 100);
        $this->assertTrue($notification->isComplete());

        $notification = new ProgressNotification('token', 150, 100);
        $this->assertTrue($notification->isComplete());

        $notification = new ProgressNotification('token', 50, 100);
        $this->assertFalse($notification->isComplete());

        $notification = new ProgressNotification('token', 50);
        $this->assertFalse($notification->isComplete());
    }

    public function testGetParams(): void
    {
        $progressToken = 'token_123';
        $progress = 50;
        $total = 100;

        $notification = new ProgressNotification($progressToken, $progress, $total);

        $params = $notification->getParams();
        $this->assertIsArray($params);
        $this->assertSame($progressToken, $params['progressToken']);
        $this->assertSame($progress, $params['progress']);
        $this->assertSame($total, $params['total']);
        $this->assertArrayNotHasKey('_meta', $params);
    }

    public function testGetParamsWithoutTotal(): void
    {
        $notification = new ProgressNotification('token', 25);

        $params = $notification->getParams();
        $this->assertIsArray($params);
        $this->assertSame('token', $params['progressToken']);
        $this->assertSame(25, $params['progress']);
        $this->assertArrayNotHasKey('total', $params);
    }

    public function testGetParamsWithMeta(): void
    {
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];
        $notification = new ProgressNotification('token', 25, null, $meta);

        $params = $notification->getParams();
        $this->assertArrayHasKey('_meta', $params);
        $this->assertSame($meta, $params['_meta']);
    }

    public function testToJsonRpc(): void
    {
        $progressToken = 'token_123';
        $progress = 75;
        $total = 100;

        $notification = new ProgressNotification($progressToken, $progress, $total);

        $jsonRpc = $notification->toJsonRpc();
        $this->assertSame('2.0', $jsonRpc['jsonrpc']);
        $this->assertSame('notifications/progress', $jsonRpc['method']);
        $this->assertIsArray($jsonRpc['params']);
        $this->assertSame($progressToken, $jsonRpc['params']['progressToken']);
        $this->assertSame($progress, $jsonRpc['params']['progress']);
        $this->assertSame($total, $jsonRpc['params']['total']);
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'params' => [
                'progressToken' => 'token_123',
                'progress' => 50,
                'total' => 100,
                '_meta' => ['timestamp' => '2025-01-01T00:00:00Z'],
            ],
        ];

        $notification = ProgressNotification::fromArray($data);

        $this->assertSame('token_123', $notification->getProgressToken());
        $this->assertSame(50, $notification->getProgress());
        $this->assertSame(100, $notification->getTotal());
        $this->assertTrue($notification->hasMeta());
        $this->assertSame(['timestamp' => '2025-01-01T00:00:00Z'], $notification->getMeta());
    }

    public function testFromArrayWithoutTotal(): void
    {
        $data = [
            'params' => [
                'progressToken' => 'token_123',
                'progress' => 25,
            ],
        ];

        $notification = ProgressNotification::fromArray($data);

        $this->assertSame('token_123', $notification->getProgressToken());
        $this->assertSame(25, $notification->getProgress());
        $this->assertNull($notification->getTotal());
        $this->assertFalse($notification->hasMeta());
    }

    public function testFromArrayMissingProgressToken(): void
    {
        $data = [
            'params' => [
                'progress' => 50,
            ],
        ];

        $this->expectException(ValidationError::class);
        ProgressNotification::fromArray($data);
    }

    public function testFromArrayMissingProgress(): void
    {
        $data = [
            'params' => [
                'progressToken' => 'token_123',
            ],
        ];

        $this->expectException(ValidationError::class);
        ProgressNotification::fromArray($data);
    }

    public function testFromArrayWithInvalidProgressType(): void
    {
        $data = [
            'params' => [
                'progressToken' => 'token_123',
                'progress' => 'invalid',
            ],
        ];

        $this->expectException(ValidationError::class);
        ProgressNotification::fromArray($data);
    }
}
