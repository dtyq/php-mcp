<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Messages;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Messages\MessageInterface;
use Dtyq\PhpMcp\Types\Messages\SamplingMessage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for SamplingMessage.
 * @internal
 */
class SamplingMessageTest extends TestCase
{
    /**
     * Test constructor with valid role and content.
     */
    public function testConstructorWithValidRoleAndContent(): void
    {
        $content = new TextContent('Hello, world!');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals(ProtocolConstants::ROLE_USER, $message->getRole());
        $this->assertSame($content, $message->getContent());
    }

    /**
     * Test constructor with assistant role.
     */
    public function testConstructorWithAssistantRole(): void
    {
        $content = new TextContent('Assistant response');
        $message = new SamplingMessage(ProtocolConstants::ROLE_ASSISTANT, $content);

        $this->assertEquals(ProtocolConstants::ROLE_ASSISTANT, $message->getRole());
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
        new SamplingMessage('invalid_role', $content);
    }

    /**
     * Test setContent method.
     */
    public function testSetContent(): void
    {
        $content1 = new TextContent('First content');
        $content2 = new TextContent('Second content');

        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content1);
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
        $userMessage = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);
        $assistantMessage = new SamplingMessage(ProtocolConstants::ROLE_ASSISTANT, $content);

        // isTargetedTo is based on content, not message role
        // TextContent without annotations returns true for any role
        $this->assertTrue($userMessage->isTargetedTo(ProtocolConstants::ROLE_USER));
        $this->assertTrue($userMessage->isTargetedTo(ProtocolConstants::ROLE_ASSISTANT));

        $this->assertTrue($assistantMessage->isTargetedTo(ProtocolConstants::ROLE_ASSISTANT));
        $this->assertTrue($assistantMessage->isTargetedTo(ProtocolConstants::ROLE_USER));
    }

    /**
     * Test getPriority returns null.
     */
    public function testGetPriority(): void
    {
        $content = new TextContent('test');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $this->assertNull($message->getPriority());
    }

    /**
     * Test toArray method.
     */
    public function testToArray(): void
    {
        $content = new TextContent('test message');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $array = $message->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('role', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertEquals(ProtocolConstants::ROLE_USER, $array['role']);
        $this->assertEquals($content->toArray(), $array['content']);
    }

    /**
     * Test toJson method.
     */
    public function testToJson(): void
    {
        $content = new TextContent('test message');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $json = $message->toJson();

        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(ProtocolConstants::ROLE_USER, $decoded['role']);
        $this->assertArrayHasKey('content', $decoded);
    }

    /**
     * Test fromArray with valid data.
     */
    public function testFromArrayWithValidData(): void
    {
        $data = [
            'role' => ProtocolConstants::ROLE_USER,
            'content' => [
                'type' => ProtocolConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello world',
            ],
        ];

        $message = SamplingMessage::fromArray($data);

        $this->assertInstanceOf(SamplingMessage::class, $message);
        $this->assertEquals(ProtocolConstants::ROLE_USER, $message->getRole());
        $this->assertInstanceOf(TextContent::class, $message->getContent());
    }

    /**
     * Test fromArray with missing role.
     */
    public function testFromArrayWithMissingRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role field is required for SamplingMessage');

        $data = [
            'content' => [
                'type' => ProtocolConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello world',
            ],
        ];

        SamplingMessage::fromArray($data);
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
                'type' => ProtocolConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello world',
            ],
        ];

        SamplingMessage::fromArray($data);
    }

    /**
     * Test fromArray with missing content.
     */
    public function testFromArrayWithMissingContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Content field is required for SamplingMessage');

        $data = [
            'role' => ProtocolConstants::ROLE_USER,
        ];

        SamplingMessage::fromArray($data);
    }

    /**
     * Test role validation with all valid roles.
     */
    public function testRoleValidationWithAllValidRoles(): void
    {
        $content = new TextContent('test');

        // Valid roles should work
        $validRoles = [
            ProtocolConstants::ROLE_USER,
            ProtocolConstants::ROLE_ASSISTANT,
        ];

        foreach ($validRoles as $role) {
            $message = new SamplingMessage($role, $content);
            $this->assertEquals($role, $message->getRole());
        }

        // Invalid role should throw exception
        $this->expectException(ValidationError::class);
        new SamplingMessage('invalid', $content);
    }

    /**
     * Test static factory methods.
     */
    public function testStaticFactoryMethods(): void
    {
        $textMessage = SamplingMessage::text(ProtocolConstants::ROLE_USER, 'Hello world');
        $this->assertInstanceOf(SamplingMessage::class, $textMessage);
        $this->assertEquals(ProtocolConstants::ROLE_USER, $textMessage->getRole());
        $this->assertInstanceOf(TextContent::class, $textMessage->getContent());
    }

    /**
     * Test withRole method.
     */
    public function testWithRole(): void
    {
        $content = new TextContent('test message');
        $originalMessage = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $newMessage = $originalMessage->withRole(ProtocolConstants::ROLE_ASSISTANT);

        $this->assertEquals(ProtocolConstants::ROLE_USER, $originalMessage->getRole());
        $this->assertEquals(ProtocolConstants::ROLE_ASSISTANT, $newMessage->getRole());
        $this->assertSame($content, $newMessage->getContent());
    }

    /**
     * Test withContent method.
     */
    public function testWithContent(): void
    {
        $content1 = new TextContent('first message');
        $content2 = new TextContent('second message');
        $originalMessage = new SamplingMessage(ProtocolConstants::ROLE_USER, $content1);

        $newMessage = $originalMessage->withContent($content2);

        $this->assertSame($content1, $originalMessage->getContent());
        $this->assertSame($content2, $newMessage->getContent());
        $this->assertEquals(ProtocolConstants::ROLE_USER, $newMessage->getRole());
    }
}
