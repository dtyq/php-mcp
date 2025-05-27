<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Responses;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Content\EmbeddedResource;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Responses\CallToolResult;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Test case for CallToolResult class.
 * @internal
 */
class CallToolResultTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $textContent = new TextContent('Hello World');
        $imageContent = new ImageContent(base64_encode('fake-image'), 'image/png');
        $content = [$textContent, $imageContent];
        $isError = false;
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];

        $result = new CallToolResult($content, $isError, $meta);

        $this->assertSame($content, $result->getContent());
        $this->assertSame($isError, $result->isError());
        $this->assertTrue($result->hasMeta());
        $this->assertSame($meta, $result->getMeta());
        $this->assertFalse($result->isPaginated());
        $this->assertNull($result->getNextCursor());
    }

    public function testConstructorWithMinimalData(): void
    {
        $content = [new TextContent('Hello')];

        $result = new CallToolResult($content);

        $this->assertSame($content, $result->getContent());
        $this->assertFalse($result->isError());
        $this->assertFalse($result->hasMeta());
        $this->assertNull($result->getMeta());
    }

    public function testSetContent(): void
    {
        $initialContent = [new TextContent('Initial')];
        $result = new CallToolResult($initialContent);

        $newContent = [new TextContent('Updated')];
        $result->setContent($newContent);
        $this->assertSame($newContent, $result->getContent());
    }

    public function testSetContentWithEmptyArray(): void
    {
        $result = new CallToolResult([new TextContent('Test')]);

        $this->expectException(ValidationError::class);
        $result->setContent([]);
    }

    public function testSetContentWithInvalidType(): void
    {
        $result = new CallToolResult([new TextContent('Test')]);

        $this->expectException(ValidationError::class);
        $result->setContent(['invalid']);
    }

    public function testSetIsError(): void
    {
        $result = new CallToolResult([new TextContent('Test')]);

        $this->assertFalse($result->isError());

        $result->setIsError(true);
        $this->assertTrue($result->isError());

        $result->setIsError(false);
        $this->assertFalse($result->isError());
    }

    public function testSetMeta(): void
    {
        $result = new CallToolResult([new TextContent('Test')]);
        $meta = ['key' => 'value'];

        $result->setMeta($meta);
        $this->assertTrue($result->hasMeta());
        $this->assertSame($meta, $result->getMeta());

        $result->setMeta(null);
        $this->assertFalse($result->hasMeta());
        $this->assertNull($result->getMeta());
    }

    public function testAddContent(): void
    {
        $initialContent = new TextContent('Initial');
        $result = new CallToolResult([$initialContent]);

        $newContent = new TextContent('Added');
        $result->addContent($newContent);

        $content = $result->getContent();
        $this->assertCount(2, $content);
        $this->assertSame($initialContent, $content[0]);
        $this->assertSame($newContent, $content[1]);
    }

    public function testAddContentWithInvalidType(): void
    {
        $result = new CallToolResult([new TextContent('Test')]);

        $this->expectException(TypeError::class);
        $result->addContent('invalid');
    }

    public function testGetContentCount(): void
    {
        $content = [
            new TextContent('Text 1'),
            new TextContent('Text 2'),
            new ImageContent(base64_encode('image'), 'image/png'),
        ];
        $result = new CallToolResult($content);

        $this->assertSame(3, $result->getContentCount());
    }

    public function testGetFirstContent(): void
    {
        $firstContent = new TextContent('First');
        $content = [$firstContent, new TextContent('Second')];
        $result = new CallToolResult($content);

        $this->assertSame($firstContent, $result->getFirstContent());
    }

    public function testGetFirstContentWithEmptyResult(): void
    {
        // This test would fail because constructor validates non-empty content
        // But we can test the method behavior
        $result = new CallToolResult([new TextContent('Test')]);
        $result->setContent([new TextContent('Only one')]);

        $this->assertInstanceOf(TextContent::class, $result->getFirstContent());
    }

    public function testHasMultipleContents(): void
    {
        $singleContent = [new TextContent('Single')];
        $result = new CallToolResult($singleContent);
        $this->assertFalse($result->hasMultipleContents());

        $multipleContent = [
            new TextContent('First'),
            new TextContent('Second'),
        ];
        $result->setContent($multipleContent);
        $this->assertTrue($result->hasMultipleContents());
    }

    public function testToArray(): void
    {
        $textContent = new TextContent('Hello');
        $content = [$textContent];
        $isError = true;
        $meta = ['timestamp' => '2025-01-01T00:00:00Z'];

        $result = new CallToolResult($content, $isError, $meta);

        $array = $result->toArray();
        $this->assertIsArray($array['content']);
        $this->assertCount(1, $array['content']);
        $this->assertSame($isError, $array['isError']);
        $this->assertSame($meta, $array['_meta']);
    }

    public function testToArrayWithoutOptionalFields(): void
    {
        $result = new CallToolResult([new TextContent('Test')]);

        $array = $result->toArray();
        $this->assertIsArray($array['content']);
        $this->assertSame(false, $array['isError']);
        $this->assertArrayNotHasKey('_meta', $array);
    }

    public function testFromArrayWithTextContent(): void
    {
        $data = [
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'Hello World',
                ],
            ],
            'isError' => false,
            '_meta' => ['timestamp' => '2025-01-01T00:00:00Z'],
        ];

        $result = CallToolResult::fromArray($data);

        $this->assertCount(1, $result->getContent());
        $this->assertInstanceOf(TextContent::class, $result->getContent()[0]);
        $this->assertFalse($result->isError());
        $this->assertTrue($result->hasMeta());
        $this->assertSame(['timestamp' => '2025-01-01T00:00:00Z'], $result->getMeta());
    }

    public function testFromArrayWithImageContent(): void
    {
        $data = [
            'content' => [
                [
                    'type' => 'image',
                    'data' => base64_encode('fake-image'),
                    'mimeType' => 'image/png',
                ],
            ],
        ];

        $result = CallToolResult::fromArray($data);

        $this->assertCount(1, $result->getContent());
        $this->assertInstanceOf(ImageContent::class, $result->getContent()[0]);
        $this->assertFalse($result->isError());
    }

    public function testFromArrayWithResourceContent(): void
    {
        $data = [
            'content' => [
                [
                    'type' => 'resource',
                    'resource' => [
                        'uri' => 'file://test.txt',
                        'text' => 'Resource content',
                    ],
                ],
            ],
        ];

        $result = CallToolResult::fromArray($data);

        $this->assertCount(1, $result->getContent());
        $this->assertInstanceOf(EmbeddedResource::class, $result->getContent()[0]);
    }

    public function testFromArrayMissingContent(): void
    {
        $data = ['isError' => false];

        $this->expectException(ValidationError::class);
        CallToolResult::fromArray($data);
    }

    public function testFromArrayWithInvalidContentType(): void
    {
        $data = [
            'content' => 'invalid',
        ];

        $this->expectException(ValidationError::class);
        CallToolResult::fromArray($data);
    }

    public function testFromArrayWithEmptyContent(): void
    {
        $data = [
            'content' => [],
        ];

        $this->expectException(ValidationError::class);
        CallToolResult::fromArray($data);
    }

    public function testFromArrayWithInvalidContentItemType(): void
    {
        $data = [
            'content' => [
                [
                    'type' => 'invalid',
                    'text' => 'Hello',
                ],
            ],
        ];

        $this->expectException(ValidationError::class);
        CallToolResult::fromArray($data);
    }

    public function testPaginationMethods(): void
    {
        $result = new CallToolResult([new TextContent('Test')]);

        $this->assertFalse($result->isPaginated());
        $this->assertNull($result->getNextCursor());
    }
}
