<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Content;

use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TextContentTest extends TestCase
{
    public function testConstructorWithValidText(): void
    {
        $text = 'Hello, world!';
        $content = new TextContent($text);

        $this->assertSame(ProtocolConstants::CONTENT_TYPE_TEXT, $content->getType());
        $this->assertSame($text, $content->getText());
        $this->assertNull($content->getAnnotations());
        $this->assertFalse($content->hasAnnotations());
    }

    public function testConstructorWithAnnotations(): void
    {
        $text = 'Hello, world!';
        $annotations = new Annotations([ProtocolConstants::ROLE_USER], 0.5);
        $content = new TextContent($text, $annotations);

        $this->assertSame($text, $content->getText());
        $this->assertSame($annotations, $content->getAnnotations());
        $this->assertTrue($content->hasAnnotations());
    }

    public function testConstructorWithEmptyText(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text content cannot be empty');

        new TextContent('');
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'type' => ProtocolConstants::CONTENT_TYPE_TEXT,
            'text' => 'Test content',
            'annotations' => [
                'audience' => [ProtocolConstants::ROLE_USER],
                'priority' => 0.7,
            ],
        ];

        $content = TextContent::fromArray($data);

        $this->assertSame($data['text'], $content->getText());
        $this->assertTrue($content->hasAnnotations());
        $this->assertSame([ProtocolConstants::ROLE_USER], $content->getAnnotations()->getAudience());
        $this->assertSame(0.7, $content->getAnnotations()->getPriority());
    }

    public function testFromArrayWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid content type for TextContent');

        TextContent::fromArray([
            'type' => 'invalid',
            'text' => 'Test',
        ]);
    }

    public function testFromArrayWithMissingText(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text field is required for TextContent');

        TextContent::fromArray([
            'type' => ProtocolConstants::CONTENT_TYPE_TEXT,
        ]);
    }

    public function testFromArrayWithNonStringText(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text field must be a string');

        TextContent::fromArray([
            'type' => ProtocolConstants::CONTENT_TYPE_TEXT,
            'text' => 123,
        ]);
    }

    public function testSetText(): void
    {
        $content = new TextContent('Initial text');
        $newText = 'Updated text';

        $content->setText($newText);

        $this->assertSame($newText, $content->getText());
    }

    public function testSetEmptyText(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text content cannot be empty');

        $content = new TextContent('Initial text');
        $content->setText('');
    }

    public function testGetLength(): void
    {
        $text = 'Hello, world!';
        $content = new TextContent($text);

        $this->assertSame(strlen($text), $content->getLength());
    }

    public function testIsEmpty(): void
    {
        $content = new TextContent('   ');
        $this->assertTrue($content->isEmpty());

        $content->setText('Not empty');
        $this->assertFalse($content->isEmpty());
    }

    public function testTruncate(): void
    {
        $content = new TextContent('This is a long text that should be truncated');

        $truncated = $content->truncate(10);
        $this->assertSame('This is...', $truncated);

        $truncated = $content->truncate(10, ' [more]');
        $this->assertSame('Thi [more]', $truncated);

        $truncated = $content->truncate(100);
        $this->assertSame($content->getText(), $truncated);
    }

    public function testIsTargetedTo(): void
    {
        $content = new TextContent('Test');
        $this->assertTrue($content->isTargetedTo(ProtocolConstants::ROLE_USER));

        $annotations = new Annotations([ProtocolConstants::ROLE_USER]);
        $content->setAnnotations($annotations);
        $this->assertTrue($content->isTargetedTo(ProtocolConstants::ROLE_USER));
        $this->assertFalse($content->isTargetedTo(ProtocolConstants::ROLE_ASSISTANT));
    }

    public function testGetPriority(): void
    {
        $content = new TextContent('Test');
        $this->assertNull($content->getPriority());

        $annotations = new Annotations(null, 0.8);
        $content->setAnnotations($annotations);
        $this->assertSame(0.8, $content->getPriority());
    }

    public function testToArray(): void
    {
        $text = 'Test content';
        $annotations = new Annotations([ProtocolConstants::ROLE_USER], 0.5);
        $content = new TextContent($text, $annotations);

        $expected = [
            'type' => ProtocolConstants::CONTENT_TYPE_TEXT,
            'text' => $text,
            'annotations' => [
                'audience' => [ProtocolConstants::ROLE_USER],
                'priority' => 0.5,
            ],
        ];

        $this->assertSame($expected, $content->toArray());
    }

    public function testToArrayWithoutAnnotations(): void
    {
        $text = 'Test content';
        $content = new TextContent($text);

        $expected = [
            'type' => ProtocolConstants::CONTENT_TYPE_TEXT,
            'text' => $text,
        ];

        $this->assertSame($expected, $content->toArray());
    }

    public function testToJson(): void
    {
        $content = new TextContent('Test content');
        $json = $content->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame(ProtocolConstants::CONTENT_TYPE_TEXT, $decoded['type']);
        $this->assertSame('Test content', $decoded['text']);
    }

    public function testWithText(): void
    {
        $original = new TextContent('Original text');
        $newText = 'New text';

        $copy = $original->withText($newText);

        $this->assertNotSame($original, $copy);
        $this->assertSame('Original text', $original->getText());
        $this->assertSame($newText, $copy->getText());
    }

    public function testWithAnnotations(): void
    {
        $original = new TextContent('Test text');
        $annotations = new Annotations([ProtocolConstants::ROLE_USER]);

        $copy = $original->withAnnotations($annotations);

        $this->assertNotSame($original, $copy);
        $this->assertNull($original->getAnnotations());
        $this->assertSame($annotations, $copy->getAnnotations());
    }
}
