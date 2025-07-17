<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Prompts;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\EmbeddedResource;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class PromptMessageTest extends TestCase
{
    public function testConstructorWithTextContent(): void
    {
        $role = MessageConstants::ROLE_USER;
        $content = new TextContent('Hello, world!');

        $message = new PromptMessage($role, $content);

        $this->assertSame($role, $message->getRole());
        $this->assertSame($content, $message->getContent());
        $this->assertTrue($message->isUserMessage());
        $this->assertFalse($message->isAssistantMessage());
        $this->assertTrue($message->isTextContent());
        $this->assertFalse($message->isImageContent());
        $this->assertFalse($message->isResourceContent());
    }

    public function testConstructorWithImageContent(): void
    {
        $role = MessageConstants::ROLE_USER;
        $content = new ImageContent('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', 'image/png');

        $message = new PromptMessage($role, $content);

        $this->assertSame($role, $message->getRole());
        $this->assertSame($content, $message->getContent());
        $this->assertTrue($message->isImageContent());
        $this->assertFalse($message->isTextContent());
        $this->assertFalse($message->isResourceContent());
    }

    public function testConstructorWithResourceContent(): void
    {
        $role = MessageConstants::ROLE_USER;
        $resourceContents = new TextResourceContents('file:///test.txt', 'Test content');
        $content = new EmbeddedResource($resourceContents);

        $message = new PromptMessage($role, $content);

        $this->assertSame($role, $message->getRole());
        $this->assertSame($content, $message->getContent());
        $this->assertTrue($message->isResourceContent());
        $this->assertFalse($message->isTextContent());
        $this->assertFalse($message->isImageContent());
    }

    public function testFromArrayWithTextContent(): void
    {
        $data = [
            'role' => MessageConstants::ROLE_USER,
            'content' => [
                'type' => MessageConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello, world!',
            ],
        ];

        $message = PromptMessage::fromArray($data);

        $this->assertSame($data['role'], $message->getRole());
        $this->assertTrue($message->isTextContent());
        $this->assertSame('Hello, world!', $message->getTextContent());
    }

    public function testFromArrayWithImageContent(): void
    {
        $data = [
            'role' => MessageConstants::ROLE_USER,
            'content' => [
                'type' => MessageConstants::CONTENT_TYPE_IMAGE,
                'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
                'mimeType' => 'image/png',
            ],
        ];

        $message = PromptMessage::fromArray($data);

        $this->assertSame($data['role'], $message->getRole());
        $this->assertTrue($message->isImageContent());
    }

    public function testFromArrayWithResourceContent(): void
    {
        $data = [
            'role' => MessageConstants::ROLE_USER,
            'content' => [
                'type' => MessageConstants::CONTENT_TYPE_RESOURCE,
                'resource' => [
                    'uri' => 'file:///test.txt',
                    'text' => 'Test content',
                ],
            ],
        ];

        $message = PromptMessage::fromArray($data);

        $this->assertSame($data['role'], $message->getRole());
        $this->assertTrue($message->isResourceContent());
    }

    public function testFromArrayWithMissingRole(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'role\' is missing for PromptMessage');

        PromptMessage::fromArray([
            'content' => [
                'type' => MessageConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello',
            ],
        ]);
    }

    public function testFromArrayWithInvalidRoleType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'role\': expected string, got integer');

        PromptMessage::fromArray([
            'role' => 123,
            'content' => [
                'type' => MessageConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello',
            ],
        ]);
    }

    public function testFromArrayWithMissingContent(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'content\' is missing for PromptMessage');

        PromptMessage::fromArray([
            'role' => MessageConstants::ROLE_USER,
        ]);
    }

    public function testFromArrayWithInvalidContentType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'content\': expected array, got string');

        PromptMessage::fromArray([
            'role' => MessageConstants::ROLE_USER,
            'content' => 'invalid',
        ]);
    }

    public function testFromArrayWithInvalidContentTypeValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid content type: expected text, image, or resource, got invalid');

        PromptMessage::fromArray([
            'role' => MessageConstants::ROLE_USER,
            'content' => [
                'type' => 'invalid',
                'text' => 'Hello',
            ],
        ]);
    }

    public function testSetRoleWithInvalidRole(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'role\': must be one of: user, assistant');

        $message = new PromptMessage(MessageConstants::ROLE_USER, new TextContent('Hello'));
        $message->setRole('invalid');
    }

    public function testIsUserMessage(): void
    {
        $userMessage = new PromptMessage(MessageConstants::ROLE_USER, new TextContent('Hello'));
        $assistantMessage = new PromptMessage(MessageConstants::ROLE_ASSISTANT, new TextContent('Hi'));

        $this->assertTrue($userMessage->isUserMessage());
        $this->assertFalse($userMessage->isAssistantMessage());

        $this->assertFalse($assistantMessage->isUserMessage());
        $this->assertTrue($assistantMessage->isAssistantMessage());
    }

    public function testGetTextContent(): void
    {
        $textMessage = new PromptMessage(MessageConstants::ROLE_USER, new TextContent('Hello'));
        $imageMessage = new PromptMessage(MessageConstants::ROLE_USER, new ImageContent('dGVzdA==', 'image/png'));

        $this->assertSame('Hello', $textMessage->getTextContent());
        $this->assertNull($imageMessage->getTextContent());
    }

    public function testGetContentType(): void
    {
        $textMessage = new PromptMessage(MessageConstants::ROLE_USER, new TextContent('Hello'));
        $imageMessage = new PromptMessage(MessageConstants::ROLE_USER, new ImageContent('dGVzdA==', 'image/png'));

        $this->assertSame(MessageConstants::CONTENT_TYPE_TEXT, $textMessage->getContentType());
        $this->assertSame(MessageConstants::CONTENT_TYPE_IMAGE, $imageMessage->getContentType());
    }

    public function testToArray(): void
    {
        $role = MessageConstants::ROLE_USER;
        $content = new TextContent('Hello, world!');
        $message = new PromptMessage($role, $content);

        $expected = [
            'role' => $role,
            'content' => [
                'type' => MessageConstants::CONTENT_TYPE_TEXT,
                'text' => 'Hello, world!',
            ],
        ];

        $this->assertSame($expected, $message->toArray());
    }

    public function testToJson(): void
    {
        $message = new PromptMessage(MessageConstants::ROLE_USER, new TextContent('Hello'));
        $json = $message->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame(MessageConstants::ROLE_USER, $decoded['role']);
        $this->assertSame(MessageConstants::CONTENT_TYPE_TEXT, $decoded['content']['type']);
        $this->assertSame('Hello', $decoded['content']['text']);
    }

    public function testWithMethods(): void
    {
        $original = new PromptMessage(MessageConstants::ROLE_USER, new TextContent('Original'));

        $withRole = $original->withRole(MessageConstants::ROLE_ASSISTANT);
        $this->assertNotSame($original, $withRole);
        $this->assertSame(MessageConstants::ROLE_USER, $original->getRole());
        $this->assertSame(MessageConstants::ROLE_ASSISTANT, $withRole->getRole());

        $newContent = new TextContent('New content');
        $withContent = $original->withContent($newContent);
        $this->assertNotSame($original, $withContent);
        $this->assertSame('Original', $original->getTextContent());
        $this->assertSame('New content', $withContent->getTextContent());
    }

    public function testFactoryMethods(): void
    {
        $userMessage = PromptMessage::createUserMessage('User text');
        $this->assertTrue($userMessage->isUserMessage());
        $this->assertTrue($userMessage->isTextContent());
        $this->assertSame('User text', $userMessage->getTextContent());

        $assistantMessage = PromptMessage::createAssistantMessage('Assistant text');
        $this->assertTrue($assistantMessage->isAssistantMessage());
        $this->assertTrue($assistantMessage->isTextContent());
        $this->assertSame('Assistant text', $assistantMessage->getTextContent());

        $resourceContents = new TextResourceContents('file:///test.txt', 'Test content');
        $resource = new EmbeddedResource($resourceContents);
        $resourceMessage = PromptMessage::createUserResourceMessage($resource);
        $this->assertTrue($resourceMessage->isUserMessage());
        $this->assertTrue($resourceMessage->isResourceContent());

        $image = new ImageContent('dGVzdA==', 'image/png');
        $imageMessage = PromptMessage::createUserImageMessage($image);
        $this->assertTrue($imageMessage->isUserMessage());
        $this->assertTrue($imageMessage->isImageContent());
    }
}
