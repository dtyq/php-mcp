<?php

declare(strict_types=1);

namespace Dtyq\PhpMcp\Tests\Unit\Types\Sampling;

use Dtyq\PhpMcp\Types\Sampling\SamplingMessage;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Content\EmbeddedResource;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

class SamplingMessageTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $content = new TextContent('Hello, world!');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $this->assertSame(ProtocolConstants::ROLE_USER, $message->getRole());
        $this->assertSame($content, $message->getContent());
        $this->assertTrue($message->isUserMessage());
        $this->assertFalse($message->isAssistantMessage());
        $this->assertTrue($message->isTextContent());
        $this->assertFalse($message->isImageContent());
        $this->assertFalse($message->isEmbeddedResourceContent());
    }

    public function testConstructorWithAssistantRole(): void
    {
        $content = new TextContent('I can help you with that.');
        $message = new SamplingMessage(ProtocolConstants::ROLE_ASSISTANT, $content);

        $this->assertSame(ProtocolConstants::ROLE_ASSISTANT, $message->getRole());
        $this->assertFalse($message->isUserMessage());
        $this->assertTrue($message->isAssistantMessage());
    }

    public function testConstructorWithImageContent(): void
    {
        $content = new ImageContent('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', 'image/png');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $this->assertFalse($message->isTextContent());
        $this->assertTrue($message->isImageContent());
        $this->assertFalse($message->isEmbeddedResourceContent());
    }

    public function testConstructorWithInvalidRole(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'role\': must be either "user" or "assistant"');

        $content = new TextContent('Hello');
        new SamplingMessage('invalid_role', $content);
    }

    public function testConstructorWithEmptyRole(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'role\' cannot be empty');

        $content = new TextContent('Hello');
        new SamplingMessage('', $content);
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'role' => ProtocolConstants::ROLE_USER,
            'content' => [
                'type' => 'text',
                'text' => 'Hello, world!',
            ],
        ];

        $message = SamplingMessage::fromArray($data);

        $this->assertSame(ProtocolConstants::ROLE_USER, $message->getRole());
        $this->assertTrue($message->isTextContent());
        $this->assertSame('Hello, world!', $message->getTextContent());
    }

    public function testFromArrayWithImageContent(): void
    {
        $data = [
            'role' => ProtocolConstants::ROLE_USER,
            'content' => [
                'type' => 'image',
                'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
                'mimeType' => 'image/png',
            ],
        ];

        $message = SamplingMessage::fromArray($data);

        $this->assertTrue($message->isImageContent());
        $this->assertSame('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', $message->getImageData());
        $this->assertSame('image/png', $message->getImageMimeType());
    }

    public function testFromArrayWithEmbeddedResource(): void
    {
        $data = [
            'role' => ProtocolConstants::ROLE_ASSISTANT,
            'content' => [
                'type' => 'resource',
                'resource' => [
                    'uri' => 'file://test.txt',
                    'text' => 'File content',
                ],
            ],
        ];

        $message = SamplingMessage::fromArray($data);

        $this->assertTrue($message->isEmbeddedResourceContent());
        $this->assertFalse($message->isTextContent());
        $this->assertFalse($message->isImageContent());
    }

    public function testFromArrayWithMissingRole(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'role\' is missing');

        $data = [
            'content' => [
                'type' => 'text',
                'text' => 'Hello',
            ],
        ];

        SamplingMessage::fromArray($data);
    }

    public function testFromArrayWithMissingContent(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'content\' is missing');

        $data = [
            'role' => ProtocolConstants::ROLE_USER,
        ];

        SamplingMessage::fromArray($data);
    }

    public function testFromArrayWithInvalidRoleType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'role\': expected string, got integer');

        $data = [
            'role' => 123,
            'content' => [
                'type' => 'text',
                'text' => 'Hello',
            ],
        ];

        SamplingMessage::fromArray($data);
    }

    public function testFromArrayWithInvalidContentType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'content\': expected array, got string');

        $data = [
            'role' => ProtocolConstants::ROLE_USER,
            'content' => 'invalid',
        ];

        SamplingMessage::fromArray($data);
    }

    public function testFromArrayWithUnsupportedContentType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Unsupported content type \'unknown\'');

        $data = [
            'role' => ProtocolConstants::ROLE_USER,
            'content' => [
                'type' => 'unknown',
            ],
        ];

        SamplingMessage::fromArray($data);
    }

    public function testCreateUserMessage(): void
    {
        $message = SamplingMessage::createUserMessage('Hello, world!');

        $this->assertSame(ProtocolConstants::ROLE_USER, $message->getRole());
        $this->assertTrue($message->isUserMessage());
        $this->assertTrue($message->isTextContent());
        $this->assertSame('Hello, world!', $message->getTextContent());
    }

    public function testCreateAssistantMessage(): void
    {
        $message = SamplingMessage::createAssistantMessage('I can help you.');

        $this->assertSame(ProtocolConstants::ROLE_ASSISTANT, $message->getRole());
        $this->assertTrue($message->isAssistantMessage());
        $this->assertTrue($message->isTextContent());
        $this->assertSame('I can help you.', $message->getTextContent());
    }

    public function testCreateUserImageMessage(): void
    {
        $message = SamplingMessage::createUserImageMessage('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', 'image/jpeg');

        $this->assertSame(ProtocolConstants::ROLE_USER, $message->getRole());
        $this->assertTrue($message->isUserMessage());
        $this->assertTrue($message->isImageContent());
        $this->assertSame('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', $message->getImageData());
        $this->assertSame('image/jpeg', $message->getImageMimeType());
    }

    public function testGetTextContentWithNonTextContent(): void
    {
        $content = new ImageContent('data', 'image/png');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $this->assertNull($message->getTextContent());
    }

    public function testGetImageDataWithNonImageContent(): void
    {
        $content = new TextContent('Hello');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $this->assertNull($message->getImageData());
        $this->assertNull($message->getImageMimeType());
    }

    public function testSetRole(): void
    {
        $content = new TextContent('Hello');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $message->setRole(ProtocolConstants::ROLE_ASSISTANT);

        $this->assertSame(ProtocolConstants::ROLE_ASSISTANT, $message->getRole());
        $this->assertTrue($message->isAssistantMessage());
        $this->assertFalse($message->isUserMessage());
    }

    public function testSetContent(): void
    {
        $originalContent = new TextContent('Hello');
        $newContent = new ImageContent('data', 'image/png');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $originalContent);

        $message->setContent($newContent);

        $this->assertSame($newContent, $message->getContent());
        $this->assertTrue($message->isImageContent());
        $this->assertFalse($message->isTextContent());
    }

    public function testWithRole(): void
    {
        $content = new TextContent('Hello');
        $original = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $modified = $original->withRole(ProtocolConstants::ROLE_ASSISTANT);

        $this->assertNotSame($original, $modified);
        $this->assertSame(ProtocolConstants::ROLE_USER, $original->getRole());
        $this->assertSame(ProtocolConstants::ROLE_ASSISTANT, $modified->getRole());
    }

    public function testWithContent(): void
    {
        $originalContent = new TextContent('Hello');
        $newContent = new ImageContent('data', 'image/png');
        $original = new SamplingMessage(ProtocolConstants::ROLE_USER, $originalContent);

        $modified = $original->withContent($newContent);

        $this->assertNotSame($original, $modified);
        $this->assertSame($originalContent, $original->getContent());
        $this->assertSame($newContent, $modified->getContent());
    }

    public function testToArray(): void
    {
        $content = new TextContent('Hello, world!');
        $message = new SamplingMessage(ProtocolConstants::ROLE_USER, $content);

        $expected = [
            'role' => ProtocolConstants::ROLE_USER,
            'content' => [
                'type' => 'text',
                'text' => 'Hello, world!',
            ],
        ];

        $this->assertSame($expected, $message->toArray());
    }

    public function testToJson(): void
    {
        $content = new TextContent('Hello');
        $message = new SamplingMessage(ProtocolConstants::ROLE_ASSISTANT, $content);

        $json = $message->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame(ProtocolConstants::ROLE_ASSISTANT, $decoded['role']);
        $this->assertSame('text', $decoded['content']['type']);
        $this->assertSame('Hello', $decoded['content']['text']);
    }
} 