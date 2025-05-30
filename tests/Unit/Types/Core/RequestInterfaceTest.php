<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Core;

use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test case for RequestInterface.
 * @internal
 */
class RequestInterfaceTest extends TestCase
{
    /**
     * Test that RequestInterface can be implemented.
     */
    public function testRequestInterfaceCanBeImplemented(): void
    {
        $request = new class implements RequestInterface {
            /** @var int|string */
            private $id = 1;

            public function getMethod(): string
            {
                return 'test/method';
            }

            public function getParams(): ?array
            {
                return ['param' => 'value'];
            }

            public function getId()
            {
                return $this->id;
            }

            public function setId($id): void
            {
                $this->id = $id;
            }

            public function toJsonRpc(): array
            {
                return [
                    'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
                    'id' => $this->getId(),
                    'method' => $this->getMethod(),
                    'params' => $this->getParams(),
                ];
            }

            public function hasProgressToken(): bool
            {
                return false;
            }

            public function getProgressToken()
            {
                return null;
            }
        };

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('test/method', $request->getMethod());
        $this->assertEquals(['param' => 'value'], $request->getParams());
        $this->assertEquals(1, $request->getId());
        $this->assertFalse($request->hasProgressToken());
        $this->assertNull($request->getProgressToken());
    }

    /**
     * Test setting request ID.
     */
    public function testSetId(): void
    {
        $request = new class implements RequestInterface {
            /** @var int|string */
            private $id = 1;

            public function getMethod(): string
            {
                return 'test/method';
            }

            public function getParams(): ?array
            {
                return null;
            }

            public function getId()
            {
                return $this->id;
            }

            public function setId($id): void
            {
                $this->id = $id;
            }

            public function toJsonRpc(): array
            {
                return [
                    'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
                    'id' => $this->getId(),
                    'method' => $this->getMethod(),
                ];
            }

            public function hasProgressToken(): bool
            {
                return false;
            }

            public function getProgressToken()
            {
                return null;
            }
        };

        $this->assertEquals(1, $request->getId());

        $request->setId('string-id');
        $this->assertEquals('string-id', $request->getId());

        $request->setId(42);
        $this->assertEquals(42, $request->getId());
    }

    /**
     * Test toJsonRpc format.
     */
    public function testToJsonRpcFormat(): void
    {
        $request = new class implements RequestInterface {
            /** @var int|string */
            private $id = 'test-id';

            public function getMethod(): string
            {
                return 'test/request';
            }

            public function getParams(): ?array
            {
                return ['key1' => 'value1', 'key2' => 123];
            }

            public function getId()
            {
                return $this->id;
            }

            public function setId($id): void
            {
                $this->id = $id;
            }

            public function toJsonRpc(): array
            {
                $result = [
                    'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
                    'id' => $this->getId(),
                    'method' => $this->getMethod(),
                ];

                if ($this->getParams() !== null) {
                    $result['params'] = $this->getParams();
                }

                return $result;
            }

            public function hasProgressToken(): bool
            {
                return false;
            }

            public function getProgressToken()
            {
                return null;
            }
        };

        $jsonRpc = $request->toJsonRpc();

        $this->assertIsArray($jsonRpc);
        $this->assertArrayHasKey('jsonrpc', $jsonRpc);
        $this->assertArrayHasKey('id', $jsonRpc);
        $this->assertArrayHasKey('method', $jsonRpc);
        $this->assertArrayHasKey('params', $jsonRpc);
        $this->assertEquals(ProtocolConstants::JSONRPC_VERSION, $jsonRpc['jsonrpc']);
        $this->assertEquals('test-id', $jsonRpc['id']);
        $this->assertEquals('test/request', $jsonRpc['method']);
        $this->assertEquals(['key1' => 'value1', 'key2' => 123], $jsonRpc['params']);
    }

    /**
     * Test request without parameters.
     */
    public function testRequestWithoutParams(): void
    {
        $request = new class implements RequestInterface {
            /** @var int|string */
            private $id = 100;

            public function getMethod(): string
            {
                return 'simple/request';
            }

            public function getParams(): ?array
            {
                return null;
            }

            public function getId()
            {
                return $this->id;
            }

            public function setId($id): void
            {
                $this->id = $id;
            }

            public function toJsonRpc(): array
            {
                return [
                    'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
                    'id' => $this->getId(),
                    'method' => $this->getMethod(),
                ];
            }

            public function hasProgressToken(): bool
            {
                return false;
            }

            public function getProgressToken()
            {
                return null;
            }
        };

        $this->assertNull($request->getParams());

        $jsonRpc = $request->toJsonRpc();
        $this->assertArrayNotHasKey('params', $jsonRpc);
        $this->assertEquals(100, $jsonRpc['id']);
    }

    /**
     * Test request with progress token.
     */
    public function testRequestWithProgressToken(): void
    {
        $request = new class implements RequestInterface {
            /** @var int|string */
            private $id = 1;

            /** @var null|string */
            private $progressToken = 'progress-123';

            public function getMethod(): string
            {
                return 'test/method';
            }

            public function getParams(): ?array
            {
                return null;
            }

            public function getId()
            {
                return $this->id;
            }

            public function setId($id): void
            {
                $this->id = $id;
            }

            public function toJsonRpc(): array
            {
                return [
                    'jsonrpc' => ProtocolConstants::JSONRPC_VERSION,
                    'id' => $this->getId(),
                    'method' => $this->getMethod(),
                ];
            }

            public function hasProgressToken(): bool
            {
                return $this->progressToken !== null;
            }

            public function getProgressToken()
            {
                return $this->progressToken;
            }
        };

        $this->assertTrue($request->hasProgressToken());
        $this->assertEquals('progress-123', $request->getProgressToken());
    }
}
