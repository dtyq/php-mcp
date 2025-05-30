<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Messages;

use Dtyq\PhpMcp\Types\Content\ContentInterface;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Messages\MessageInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test case for MessageInterface.
 * @internal
 */
class MessageInterfaceTest extends TestCase
{
    /**
     * Test that MessageInterface can be implemented.
     */
    public function testMessageInterfaceCanBeImplemented(): void
    {
        $content = new TextContent('test message');

        $message = new class($content) implements MessageInterface {
            private ContentInterface $content;

            private string $role;

            private ?float $priority;

            public function __construct(ContentInterface $content, string $role = 'user', ?float $priority = null)
            {
                $this->content = $content;
                $this->role = $role;
                $this->priority = $priority;
            }

            public function getRole(): string
            {
                return $this->role;
            }

            public function getContent(): ContentInterface
            {
                return $this->content;
            }

            public function setContent(ContentInterface $content): void
            {
                $this->content = $content;
            }

            public function isTargetedTo(string $role): bool
            {
                return $this->role === $role;
            }

            public function getPriority(): ?float
            {
                return $this->priority;
            }

            public function toArray(): array
            {
                return [
                    'role' => $this->role,
                    'content' => $this->content->toArray(),
                    'priority' => $this->priority,
                ];
            }

            public function toJson(): string
            {
                return json_encode($this->toArray());
            }
        };

        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals('user', $message->getRole());
        $this->assertSame($content, $message->getContent());
        $this->assertTrue($message->isTargetedTo('user'));
        $this->assertFalse($message->isTargetedTo('assistant'));
        $this->assertNull($message->getPriority());

        $array = $message->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('role', $array);
        $this->assertArrayHasKey('content', $array);

        $json = $message->toJson();
        $this->assertIsString($json);
        $this->assertJson($json);
    }

    /**
     * Test setting new content.
     */
    public function testSetContent(): void
    {
        $content1 = new TextContent('first message');
        $content2 = new TextContent('second message');

        $message = new class($content1) implements MessageInterface {
            private ContentInterface $content;

            public function __construct(ContentInterface $content)
            {
                $this->content = $content;
            }

            public function getRole(): string
            {
                return 'user';
            }

            public function getContent(): ContentInterface
            {
                return $this->content;
            }

            public function setContent(ContentInterface $content): void
            {
                $this->content = $content;
            }

            public function isTargetedTo(string $role): bool
            {
                return true;
            }

            public function getPriority(): ?float
            {
                return null;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson(): string
            {
                return '{}';
            }
        };

        $this->assertSame($content1, $message->getContent());

        $message->setContent($content2);
        $this->assertSame($content2, $message->getContent());
        $this->assertNotSame($content1, $message->getContent());
    }

    /**
     * Test priority handling.
     */
    public function testPriorityHandling(): void
    {
        $content = new TextContent('priority test');

        $message = new class($content, 0.8) implements MessageInterface {
            private ContentInterface $content;

            private ?float $priority;

            public function __construct(ContentInterface $content, ?float $priority = null)
            {
                $this->content = $content;
                $this->priority = $priority;
            }

            public function getRole(): string
            {
                return 'user';
            }

            public function getContent(): ContentInterface
            {
                return $this->content;
            }

            public function setContent(ContentInterface $content): void
            {
                $this->content = $content;
            }

            public function isTargetedTo(string $role): bool
            {
                return true;
            }

            public function getPriority(): ?float
            {
                return $this->priority;
            }

            public function toArray(): array
            {
                return ['priority' => $this->priority];
            }

            public function toJson(): string
            {
                return json_encode($this->toArray());
            }
        };

        $this->assertEquals(0.8, $message->getPriority());

        $array = $message->toArray();
        $this->assertEquals(0.8, $array['priority']);
    }

    /**
     * Test role targeting functionality.
     */
    public function testRoleTargeting(): void
    {
        $content = new TextContent('test content');

        $userMessage = new class($content, 'user') implements MessageInterface {
            private ContentInterface $content;

            private string $role;

            public function __construct(ContentInterface $content, string $role)
            {
                $this->content = $content;
                $this->role = $role;
            }

            public function getRole(): string
            {
                return $this->role;
            }

            public function getContent(): ContentInterface
            {
                return $this->content;
            }

            public function setContent(ContentInterface $content): void
            {
                $this->content = $content;
            }

            public function isTargetedTo(string $role): bool
            {
                return $this->role === $role;
            }

            public function getPriority(): ?float
            {
                return null;
            }

            public function toArray(): array
            {
                return ['role' => $this->role];
            }

            public function toJson(): string
            {
                return json_encode($this->toArray());
            }
        };

        $this->assertTrue($userMessage->isTargetedTo('user'));
        $this->assertFalse($userMessage->isTargetedTo('assistant'));
        $this->assertFalse($userMessage->isTargetedTo('system'));
    }
}
