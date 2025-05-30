<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Core;

use Dtyq\PhpMcp\Types\Core\ResultInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ResultInterface.
 * @internal
 */
class ResultInterfaceTest extends TestCase
{
    /**
     * Test that ResultInterface can be implemented.
     */
    public function testResultInterfaceCanBeImplemented(): void
    {
        $result = new class implements ResultInterface {
            /** @var null|array<string, mixed> */
            private ?array $meta = null;

            public function toArray(): array
            {
                return ['data' => 'test result'];
            }

            public function hasMeta(): bool
            {
                return $this->meta !== null;
            }

            public function getMeta(): ?array
            {
                return $this->meta;
            }

            public function setMeta(?array $meta): void
            {
                $this->meta = $meta;
            }

            public function isPaginated(): bool
            {
                return false;
            }

            public function getNextCursor(): ?string
            {
                return null;
            }
        };

        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertIsArray($result->toArray());
        $this->assertFalse($result->hasMeta());
        $this->assertNull($result->getMeta());
        $this->assertFalse($result->isPaginated());
        $this->assertNull($result->getNextCursor());
    }

    /**
     * Test setting meta information.
     */
    public function testSetMeta(): void
    {
        $result = new class implements ResultInterface {
            /** @var null|array<string, mixed> */
            private ?array $meta = null;

            public function toArray(): array
            {
                return ['data' => 'test'];
            }

            public function hasMeta(): bool
            {
                return $this->meta !== null;
            }

            public function getMeta(): ?array
            {
                return $this->meta;
            }

            public function setMeta(?array $meta): void
            {
                $this->meta = $meta;
            }

            public function isPaginated(): bool
            {
                return false;
            }

            public function getNextCursor(): ?string
            {
                return null;
            }
        };

        $this->assertFalse($result->hasMeta());

        $meta = ['timestamp' => '2023-01-01T00:00:00Z', 'version' => '1.0'];
        $result->setMeta($meta);

        $this->assertTrue($result->hasMeta());
        $this->assertEquals($meta, $result->getMeta());

        $result->setMeta(null);
        $this->assertFalse($result->hasMeta());
        $this->assertNull($result->getMeta());
    }

    /**
     * Test paginated result.
     */
    public function testPaginatedResult(): void
    {
        $result = new class implements ResultInterface {
            /** @var null|array<string, mixed> */
            private ?array $meta = null;

            private ?string $nextCursor = 'next-page-token';

            public function toArray(): array
            {
                return ['items' => ['item1', 'item2']];
            }

            public function hasMeta(): bool
            {
                return $this->meta !== null;
            }

            public function getMeta(): ?array
            {
                return $this->meta;
            }

            public function setMeta(?array $meta): void
            {
                $this->meta = $meta;
            }

            public function isPaginated(): bool
            {
                return $this->nextCursor !== null;
            }

            public function getNextCursor(): ?string
            {
                return $this->nextCursor;
            }
        };

        $this->assertTrue($result->isPaginated());
        $this->assertEquals('next-page-token', $result->getNextCursor());
    }

    /**
     * Test toArray method.
     */
    public function testToArray(): void
    {
        $result = new class implements ResultInterface {
            public function toArray(): array
            {
                return [
                    'status' => 'success',
                    'data' => ['key' => 'value'],
                    'count' => 1,
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

            public function setMeta(?array $meta): void
            {
                // No-op for this test
            }

            public function isPaginated(): bool
            {
                return false;
            }

            public function getNextCursor(): ?string
            {
                return null;
            }
        };

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('count', $array);
        $this->assertEquals('success', $array['status']);
        $this->assertEquals(['key' => 'value'], $array['data']);
        $this->assertEquals(1, $array['count']);
    }
}
