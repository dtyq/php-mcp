<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Content;

use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Content\ContentInterface;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ContentInterface.
 * @internal
 */
class ContentInterfaceTest extends TestCase
{
    /**
     * Test that ContentInterface can be implemented.
     */
    public function testContentInterfaceCanBeImplemented(): void
    {
        $content = new class implements ContentInterface {
            private ?Annotations $annotations = null;

            public function getType(): string
            {
                return ProtocolConstants::CONTENT_TYPE_TEXT;
            }

            public function getAnnotations(): ?Annotations
            {
                return $this->annotations;
            }

            public function setAnnotations(?Annotations $annotations): void
            {
                $this->annotations = $annotations;
            }

            public function hasAnnotations(): bool
            {
                return $this->annotations !== null;
            }

            public function toArray(): array
            {
                $result = ['type' => $this->getType()];

                if ($this->hasAnnotations()) {
                    $result['annotations'] = $this->annotations->toArray();
                }

                return $result;
            }

            public function toJson(): string
            {
                return json_encode($this->toArray());
            }

            public function isTargetedTo(string $role): bool
            {
                return $this->hasAnnotations() && $this->annotations->isTargetedTo($role);
            }

            public function getPriority(): ?float
            {
                return $this->hasAnnotations() ? $this->annotations->getPriority() : null;
            }
        };

        $this->assertInstanceOf(ContentInterface::class, $content);
        $this->assertEquals(ProtocolConstants::CONTENT_TYPE_TEXT, $content->getType());
        $this->assertNull($content->getAnnotations());
        $this->assertFalse($content->hasAnnotations());
        $this->assertNull($content->getPriority());
        $this->assertFalse($content->isTargetedTo(ProtocolConstants::ROLE_USER));
    }

    /**
     * Test setting annotations.
     */
    public function testSetAnnotations(): void
    {
        $content = new class implements ContentInterface {
            private ?Annotations $annotations = null;

            public function getType(): string
            {
                return ProtocolConstants::CONTENT_TYPE_TEXT;
            }

            public function getAnnotations(): ?Annotations
            {
                return $this->annotations;
            }

            public function setAnnotations(?Annotations $annotations): void
            {
                $this->annotations = $annotations;
            }

            public function hasAnnotations(): bool
            {
                return $this->annotations !== null;
            }

            public function toArray(): array
            {
                return ['type' => $this->getType()];
            }

            public function toJson(): string
            {
                return json_encode($this->toArray());
            }

            public function isTargetedTo(string $role): bool
            {
                return $this->hasAnnotations() && $this->annotations->isTargetedTo($role);
            }

            public function getPriority(): ?float
            {
                return $this->hasAnnotations() ? $this->annotations->getPriority() : null;
            }
        };

        $this->assertFalse($content->hasAnnotations());

        $annotations = new Annotations([ProtocolConstants::ROLE_USER], 0.8);
        $content->setAnnotations($annotations);

        $this->assertTrue($content->hasAnnotations());
        $this->assertSame($annotations, $content->getAnnotations());
        $this->assertEquals(0.8, $content->getPriority());
        $this->assertTrue($content->isTargetedTo(ProtocolConstants::ROLE_USER));

        $content->setAnnotations(null);
        $this->assertFalse($content->hasAnnotations());
        $this->assertNull($content->getAnnotations());
        $this->assertNull($content->getPriority());
    }

    /**
     * Test toArray method.
     */
    public function testToArray(): void
    {
        $content = new class implements ContentInterface {
            private ?Annotations $annotations = null;

            public function getType(): string
            {
                return ProtocolConstants::CONTENT_TYPE_IMAGE;
            }

            public function getAnnotations(): ?Annotations
            {
                return $this->annotations;
            }

            public function setAnnotations(?Annotations $annotations): void
            {
                $this->annotations = $annotations;
            }

            public function hasAnnotations(): bool
            {
                return $this->annotations !== null;
            }

            public function toArray(): array
            {
                $result = [
                    'type' => $this->getType(),
                    'data' => 'test-data',
                ];

                if ($this->hasAnnotations()) {
                    $result['annotations'] = $this->annotations->toArray();
                }

                return $result;
            }

            public function toJson(): string
            {
                return json_encode($this->toArray());
            }

            public function isTargetedTo(string $role): bool
            {
                return $this->hasAnnotations() && $this->annotations->isTargetedTo($role);
            }

            public function getPriority(): ?float
            {
                return $this->hasAnnotations() ? $this->annotations->getPriority() : null;
            }
        };

        $array = $content->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('type', $array);
        $this->assertEquals(ProtocolConstants::CONTENT_TYPE_IMAGE, $array['type']);
        $this->assertArrayNotHasKey('annotations', $array);

        // Test with annotations
        $annotations = new Annotations([ProtocolConstants::ROLE_ASSISTANT], 0.5);
        $content->setAnnotations($annotations);

        $arrayWithAnnotations = $content->toArray();
        $this->assertArrayHasKey('annotations', $arrayWithAnnotations);
    }

    /**
     * Test toJson method.
     */
    public function testToJson(): void
    {
        $content = new class implements ContentInterface {
            public function getType(): string
            {
                return ProtocolConstants::CONTENT_TYPE_TEXT;
            }

            public function getAnnotations(): ?Annotations
            {
                return null;
            }

            public function setAnnotations(?Annotations $annotations): void
            {
                // No-op for this test
            }

            public function hasAnnotations(): bool
            {
                return false;
            }

            public function toArray(): array
            {
                return [
                    'type' => $this->getType(),
                    'text' => 'Hello world',
                ];
            }

            public function toJson(): string
            {
                return json_encode($this->toArray());
            }

            public function isTargetedTo(string $role): bool
            {
                return false;
            }

            public function getPriority(): ?float
            {
                return null;
            }
        };

        $json = $content->toJson();

        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(ProtocolConstants::CONTENT_TYPE_TEXT, $decoded['type']);
        $this->assertEquals('Hello world', $decoded['text']);
    }
}
