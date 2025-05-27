<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Tools;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Content\EmbeddedResource;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use Dtyq\PhpMcp\Types\Tools\ToolResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ToolResultTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $content = [
            new TextContent('Hello'),
            new TextContent('World'),
        ];

        $result = new ToolResult($content, false);

        $this->assertSame($content, $result->getContent());
        $this->assertFalse($result->isError());
        $this->assertTrue($result->isSuccess());
        $this->assertSame(2, $result->getContentCount());
    }

    public function testConstructorWithError(): void
    {
        $content = [new TextContent('Error message')];

        $result = new ToolResult($content, true);

        $this->assertTrue($result->isError());
        $this->assertFalse($result->isSuccess());
    }

    public function testConstructorWithEmptyContent(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'content\' cannot be empty');

        new ToolResult([]);
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'Hello World',
                ],
                [
                    'type' => 'image',
                    'data' => base64_encode('fake-image-data'),
                    'mimeType' => 'image/png',
                ],
            ],
            'isError' => false,
        ];

        $result = ToolResult::fromArray($data);

        $this->assertSame(2, $result->getContentCount());
        $this->assertFalse($result->isError());

        $content = $result->getContent();
        $this->assertInstanceOf(TextContent::class, $content[0]);
        $this->assertInstanceOf(ImageContent::class, $content[1]);
        /** @var TextContent $textContent */
        $textContent = $content[0];
        $this->assertSame('Hello World', $textContent->getText());
    }

    public function testFromArrayWithEmbeddedResource(): void
    {
        $data = [
            'content' => [
                [
                    'type' => 'resource',
                    'resource' => [
                        'uri' => 'file:///test.txt',
                        'text' => 'Resource content',
                    ],
                ],
            ],
        ];

        $result = ToolResult::fromArray($data);

        $this->assertSame(1, $result->getContentCount());
        $content = $result->getContent();
        $this->assertInstanceOf(EmbeddedResource::class, $content[0]);
    }

    public function testFromArrayWithMissingContent(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'content\' is missing for ToolResult');

        ToolResult::fromArray([]);
    }

    public function testFromArrayWithInvalidContentType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'content\': expected array, got string');

        ToolResult::fromArray(['content' => 'invalid']);
    }

    public function testFromArrayWithInvalidContentItem(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'content\': all items must be arrays');

        ToolResult::fromArray(['content' => ['invalid']]);
    }

    public function testFromArrayWithMissingContentType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'type\' is missing for content item');

        ToolResult::fromArray(['content' => [['text' => 'hello']]]);
    }

    public function testFromArrayWithUnknownContentType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Unsupported content type \'unknown\' for ToolResult');

        ToolResult::fromArray(['content' => [['type' => 'unknown']]]);
    }

    public function testFromArrayWithInvalidIsErrorType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'isError\': expected boolean, got string');

        ToolResult::fromArray([
            'content' => [['type' => 'text', 'text' => 'hello']],
            'isError' => 'true',
        ]);
    }

    public function testSuccessFactory(): void
    {
        $result = ToolResult::success('Operation completed');

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isError());
        $this->assertSame(1, $result->getContentCount());
        $this->assertSame('Operation completed', $result->getTextContent());
    }

    public function testErrorFactory(): void
    {
        $result = ToolResult::error('Something went wrong');

        $this->assertTrue($result->isError());
        $this->assertFalse($result->isSuccess());
        $this->assertSame(1, $result->getContentCount());
        $this->assertSame('Something went wrong', $result->getTextContent());
    }

    public function testCreateWithContentFactory(): void
    {
        $content = [
            new TextContent('Hello'),
            new ImageContent(base64_encode('image'), 'image/png'),
        ];

        $result = ToolResult::createWithContent($content, true);

        $this->assertSame($content, $result->getContent());
        $this->assertTrue($result->isError());
    }

    public function testGetFirstAndLastContent(): void
    {
        $first = new TextContent('First');
        $last = new TextContent('Last');
        $content = [$first, new TextContent('Middle'), $last];

        $result = new ToolResult($content);

        $this->assertSame($first, $result->getFirstContent());
        $this->assertSame($last, $result->getLastContent());
    }

    public function testGetFirstContentWithEmptyResult(): void
    {
        $result = new ToolResult([new TextContent('test')]);
        $result->setContent([new TextContent('only')]);

        $this->assertInstanceOf(TextContent::class, $result->getFirstContent());
    }

    public function testAddContent(): void
    {
        $result = new ToolResult([new TextContent('Initial')]);
        $newContent = new TextContent('Added');

        $result->addContent($newContent);

        $this->assertSame(2, $result->getContentCount());
        $this->assertSame($newContent, $result->getLastContent());
    }

    public function testGetTextContent(): void
    {
        $content = [
            new TextContent('Hello'),
            new ImageContent(base64_encode('image'), 'image/png'),
            new TextContent('World'),
        ];

        $result = new ToolResult($content);

        $this->assertSame("Hello\nWorld", $result->getTextContent());
    }

    public function testGetTextContentItems(): void
    {
        $text1 = new TextContent('Hello');
        $text2 = new TextContent('World');
        $content = [
            $text1,
            new ImageContent(base64_encode('image'), 'image/png'),
            $text2,
        ];

        $result = new ToolResult($content);
        $textItems = $result->getTextContentItems();

        $this->assertCount(2, $textItems);
        $this->assertSame($text1, $textItems[0]);
        $this->assertSame($text2, $textItems[1]);
    }

    public function testGetImageContentItems(): void
    {
        $image = new ImageContent(base64_encode('image'), 'image/png');
        $content = [
            new TextContent('Hello'),
            $image,
        ];

        $result = new ToolResult($content);
        $imageItems = $result->getImageContentItems();

        $this->assertCount(1, $imageItems);
        $this->assertSame($image, $imageItems[0]);
    }

    public function testGetEmbeddedResourceItems(): void
    {
        $resource = new EmbeddedResource(new TextResourceContents('file:///test.txt', 'content'));
        $content = [
            new TextContent('Hello'),
            $resource,
        ];

        $result = new ToolResult($content);
        $resourceItems = $result->getEmbeddedResourceItems();

        $this->assertCount(1, $resourceItems);
        $this->assertSame($resource, $resourceItems[0]);
    }

    public function testHasContentMethods(): void
    {
        $content = [
            new TextContent('Hello'),
            new ImageContent(base64_encode('image'), 'image/png'),
            new EmbeddedResource(new TextResourceContents('file:///test.txt', 'content')),
        ];

        $result = new ToolResult($content);

        $this->assertTrue($result->hasTextContent());
        $this->assertTrue($result->hasImageContent());
        $this->assertTrue($result->hasEmbeddedResourceContent());
    }

    public function testHasContentMethodsWithoutContent(): void
    {
        $result = new ToolResult([new TextContent('Only text')]);

        $this->assertTrue($result->hasTextContent());
        $this->assertFalse($result->hasImageContent());
        $this->assertFalse($result->hasEmbeddedResourceContent());
    }

    public function testToArray(): void
    {
        $content = [
            new TextContent('Hello'),
            new ImageContent(base64_encode('image'), 'image/png'),
        ];

        $result = new ToolResult($content, true);

        $array = $result->toArray();

        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('isError', $array);
        $this->assertTrue($array['isError']);
        $this->assertCount(2, $array['content']);
    }

    public function testToArrayWithoutError(): void
    {
        $result = new ToolResult([new TextContent('Hello')]);

        $array = $result->toArray();

        $this->assertArrayHasKey('content', $array);
        $this->assertArrayNotHasKey('isError', $array);
    }

    public function testToJson(): void
    {
        $result = new ToolResult([new TextContent('Hello World')]);
        $json = $result->toJson();
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('content', $decoded);
        $this->assertCount(1, $decoded['content']);
        $this->assertSame('text', $decoded['content'][0]['type']);
        $this->assertSame('Hello World', $decoded['content'][0]['text']);
    }

    public function testWithMethods(): void
    {
        $original = new ToolResult([new TextContent('Original')]);

        $newContent = [new TextContent('New')];
        $withContent = $original->withContent($newContent);
        $this->assertNotSame($original, $withContent);
        $this->assertSame('Original', $original->getTextContent());
        $this->assertSame('New', $withContent->getTextContent());

        $withError = $original->withIsError(true);
        $this->assertNotSame($original, $withError);
        $this->assertFalse($original->isError());
        $this->assertTrue($withError->isError());

        $additionalContent = new TextContent('Additional');
        $withAdditional = $original->withAdditionalContent($additionalContent);
        $this->assertNotSame($original, $withAdditional);
        $this->assertSame(1, $original->getContentCount());
        $this->assertSame(2, $withAdditional->getContentCount());
        $this->assertSame($additionalContent, $withAdditional->getLastContent());
    }
}
