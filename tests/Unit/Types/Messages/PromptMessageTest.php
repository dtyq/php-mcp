<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Messages;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Messages\MessageInterface;
use Dtyq\PhpMcp\Types\Messages\PromptMessage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for PromptMessage.
 * @internal
 */
class PromptMessageTest extends TestCase
{
    /**
     * Test constructor with valid role and content.
     */
    public function testConstructorWithValidRoleAndContent(): void
    {
        $content = new TextContent('Hello, world!');
        $message = new PromptMessage(MessageConstants::ROLE_USER, $content);

        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals(MessageConstants::ROLE_USER, $message->getRole());
        $this->assertSame($content, $message->getContent());
    }

    /**
     * Test constructor with assistant role.
     */
    public function testConstructorWithAssistantRole(): void
    {
        $content = new TextContent('I can help you');
        $message = new PromptMessage(MessageConstants::ROLE_ASSISTANT, $content);

        $this->assertEquals(MessageConstants::ROLE_ASSISTANT, $message->getRole());
        $this->assertSame($content, $message->getContent());
    }

    /**
     * Test constructor with invalid role.
     */
    public function testConstructorWithInvalidRole(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'role\'');

        $content = new TextContent('test');
        new PromptMessage('invalid_role', $content);
    }

    /**
     * Test setContent method.
     */
    public function testSetContent(): void
    {
        $content1 = new TextContent('First content');
        $content2 = new TextContent('Second content');

        $message = new PromptMessage(MessageConstants::ROLE_USER, $content1);
        $this->assertSame($content1, $message->getContent());

        $message->setContent($content2);
        $this->assertSame($content2, $message->getContent());
        $this->assertNotSame($content1, $message->getContent());
    }

    /**
     * Test isTargetedTo method.
     */
    public function testIsTargetedTo(): void
    {
        $content = new TextContent('test message');
        $userMessage = new PromptMessage(MessageConstants::ROLE_USER, $content);
        $assistantMessage = new PromptMessage(MessageConstants::ROLE_ASSISTANT, $content);

        // isTargetedTo is based on content, not message role
        // TextContent without annotations returns true for any role
        $this->assertTrue($userMessage->isTargetedTo(MessageConstants::ROLE_USER));
        $this->assertTrue($userMessage->isTargetedTo(MessageConstants::ROLE_ASSISTANT));

        $this->assertTrue($assistantMessage->isTargetedTo(MessageConstants::ROLE_ASSISTANT));
        $this->assertTrue($assistantMessage->isTargetedTo(MessageConstants::ROLE_USER));
    }

    /**
     * Test getPriority returns null.
     */
    public function testGetPriority(): void
    {
        $content = new TextContent('test');
        $message = new PromptMessage(MessageConstants::ROLE_USER, $content);

        $this->assertNull($message->getPriority());
    }

    /**
     * Test toArray method.
     */
    public function testToArray(): void
    {
        $content = new TextContent('test message');
        $message = new PromptMessage(MessageConstants::ROLE_USER, $content);

        $array = $message->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('role', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertEquals(MessageConstants::ROLE_USER, $array['role']);
        $this->assertEquals($content->toArray(), $array['content']);
    }

    /**
     * Test toJson method.
     */
    public function testToJson(): void
    {
        $content = new TextContent('test message');
        $message = new PromptMessage(MessageConstants::ROLE_USER, $content);

        $json = $message->toJson();

        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(MessageConstants::ROLE_USER, $decoded['role']);
        $this->assertArrayHasKey('content', $decoded);
    }

    /**
     * Test fromArray with valid data.
     */
    public function testFromArrayWithValidData(): void
    {
        $data = [
            'role' => MessageConstants::ROLE_USER,
            'content' => [
                'type' => MessageConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello world',
            ],
        ];

        $message = PromptMessage::fromArray($data);

        $this->assertInstanceOf(PromptMessage::class, $message);
        $this->assertEquals(MessageConstants::ROLE_USER, $message->getRole());
        $this->assertInstanceOf(TextContent::class, $message->getContent());
    }

    /**
     * Test fromArray with missing role.
     */
    public function testFromArrayWithMissingRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role field is required for PromptMessage');

        $data = [
            'content' => [
                'type' => MessageConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello world',
            ],
        ];

        PromptMessage::fromArray($data);
    }

    /**
     * Test fromArray with non-string role.
     */
    public function testFromArrayWithNonStringRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role field must be a string');

        $data = [
            'role' => 123,
            'content' => [
                'type' => MessageConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello world',
            ],
        ];

        PromptMessage::fromArray($data);
    }

    /**
     * Test fromArray with missing content.
     */
    public function testFromArrayWithMissingContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Content field is required for PromptMessage');

        $data = [
            'role' => MessageConstants::ROLE_USER,
        ];

        PromptMessage::fromArray($data);
    }

    /**
     * Test role validation.
     */
    public function testRoleValidation(): void
    {
        $content = new TextContent('test');

        // Valid roles should work
        $validRoles = [
            MessageConstants::ROLE_USER,
            MessageConstants::ROLE_ASSISTANT,
        ];

        foreach ($validRoles as $role) {
            $message = new PromptMessage($role, $content);
            $this->assertEquals($role, $message->getRole());
        }

        // Invalid role should throw exception
        $this->expectException(ValidationError::class);
        new PromptMessage('invalid', $content);
    }
}
