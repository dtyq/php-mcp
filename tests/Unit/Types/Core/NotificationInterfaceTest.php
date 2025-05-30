<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Core;

use Dtyq\PhpMcp\Types\Core\NotificationInterface;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\TestCase;

/**
 * Test case for NotificationInterface.
 * @internal
 */
class NotificationInterfaceTest extends TestCase
{
    /**
     * Test that NotificationInterface can be implemented.
     */
    public function testNotificationInterfaceCanBeImplemented(): void
    {
        $notification = new class implements NotificationInterface {
            public function getMethod(): string
            {
                return 'test/notification';
            }

            public function getParams(): ?array
            {
                return ['key' => 'value'];
            }

            public function toJsonRpc(): array
            {
                return [
                    'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
                    'method' => $this->getMethod(),
                    'params' => $this->getParams(),
                ];
            }

            public function hasMeta(): bool
            {
                return false;
            }

            public function getMeta(): ?array
            {
                return null;
            }
        };

        $this->assertInstanceOf(NotificationInterface::class, $notification);
        $this->assertEquals('test/notification', $notification->getMethod());
        $this->assertEquals(['key' => 'value'], $notification->getParams());
        $this->assertFalse($notification->hasMeta());
        $this->assertNull($notification->getMeta());
    }

    /**
     * Test notification with meta information.
     */
    public function testNotificationWithMeta(): void
    {
        $notification = new class implements NotificationInterface {
            public function getMethod(): string
            {
                return 'test/notification';
            }

            public function getParams(): ?array
            {
                return null;
            }

            public function toJsonRpc(): array
            {
                return [
                    'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
                    'method' => $this->getMethod(),
                ];
            }

            public function hasMeta(): bool
            {
                return true;
            }

            public function getMeta(): ?array
            {
                return ['timestamp' => '2023-01-01T00:00:00Z'];
            }
        };

        $this->assertTrue($notification->hasMeta());
        $this->assertEquals(['timestamp' => '2023-01-01T00:00:00Z'], $notification->getMeta());
    }

    /**
     * Test toJsonRpc format.
     */
    public function testToJsonRpcFormat(): void
    {
        $notification = new class implements NotificationInterface {
            public function getMethod(): string
            {
                return 'test/method';
            }

            public function getParams(): ?array
            {
                return ['param1' => 'value1', 'param2' => 42];
            }

            public function toJsonRpc(): array
            {
                $result = [
                    'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
                    'method' => $this->getMethod(),
                ];

                if ($this->getParams() !== null) {
                    $result['params'] = $this->getParams();
                }

                return $result;
            }

            public function hasMeta(): bool
            {
                return false;
            }

            public function getMeta(): ?array
            {
                return null;
            }
        };

        $jsonRpc = $notification->toJsonRpc();

        $this->assertIsArray($jsonRpc);
        $this->assertArrayHasKey('jsonrpc', $jsonRpc);
        $this->assertArrayHasKey('method', $jsonRpc);
        $this->assertArrayHasKey('params', $jsonRpc);
        $this->assertEquals(ProtocolConstants::JSONRPC_VERSION, $jsonRpc['jsonrpc']);
        $this->assertEquals('test/method', $jsonRpc['method']);
        $this->assertEquals(['param1' => 'value1', 'param2' => 42], $jsonRpc['params']);
    }

    /**
     * Test notification without parameters.
     */
    public function testNotificationWithoutParams(): void
    {
        $notification = new class implements NotificationInterface {
            public function getMethod(): string
            {
                return 'simple/notification';
            }

            public function getParams(): ?array
            {
                return null;
            }

            public function toJsonRpc(): array
            {
                return [
                    'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
                    'method' => $this->getMethod(),
                ];
            }

            public function hasMeta(): bool
            {
                return false;
            }

            public function getMeta(): ?array
            {
                return null;
            }
        };

        $this->assertNull($notification->getParams());

        $jsonRpc = $notification->toJsonRpc();
        $this->assertArrayNotHasKey('params', $jsonRpc);
    }
}
