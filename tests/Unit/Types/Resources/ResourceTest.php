<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Resources;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Resources\Resource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ResourceTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $uri = 'https://example.com/file.txt';
        $name = 'Test File';
        $description = 'A test file';
        $mimeType = 'text/plain';
        $size = 1024;
        $annotations = new Annotations([MessageConstants::ROLE_USER], 0.5);

        $resource = new Resource($uri, $name, $description, $mimeType, $size, $annotations);

        $this->assertSame($uri, $resource->getUri());
        $this->assertSame($name, $resource->getName());
        $this->assertSame($description, $resource->getDescription());
        $this->assertSame($mimeType, $resource->getMimeType());
        $this->assertSame($size, $resource->getSize());
        $this->assertSame($annotations, $resource->getAnnotations());
        $this->assertTrue($resource->hasAnnotations());
    }

    public function testConstructorWithMinimalData(): void
    {
        $uri = 'https://example.com/file.txt';
        $name = 'Test File';

        $resource = new Resource($uri, $name);

        $this->assertSame($uri, $resource->getUri());
        $this->assertSame($name, $resource->getName());
        $this->assertNull($resource->getDescription());
        $this->assertNull($resource->getMimeType());
        $this->assertNull($resource->getSize());
        $this->assertNull($resource->getAnnotations());
        $this->assertFalse($resource->hasAnnotations());
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'uri' => 'https://example.com/file.txt',
            'name' => 'Test File',
            'description' => 'A test file',
            'mimeType' => 'text/plain',
            'size' => 1024,
            'annotations' => [
                'audience' => [MessageConstants::ROLE_USER],
                'priority' => 0.7,
            ],
        ];

        $resource = Resource::fromArray($data);

        $this->assertSame($data['uri'], $resource->getUri());
        $this->assertSame($data['name'], $resource->getName());
        $this->assertSame($data['description'], $resource->getDescription());
        $this->assertSame($data['mimeType'], $resource->getMimeType());
        $this->assertSame($data['size'], $resource->getSize());
        $this->assertTrue($resource->hasAnnotations());
        $this->assertSame([MessageConstants::ROLE_USER], $resource->getAnnotations()->getAudience());
        $this->assertSame(0.7, $resource->getAnnotations()->getPriority());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'uri' => 'https://example.com/file.txt',
            'name' => 'Test File',
        ];

        $resource = Resource::fromArray($data);

        $this->assertSame($data['uri'], $resource->getUri());
        $this->assertSame($data['name'], $resource->getName());
        $this->assertNull($resource->getDescription());
        $this->assertNull($resource->getMimeType());
        $this->assertNull($resource->getSize());
        $this->assertFalse($resource->hasAnnotations());
    }

    public function testFromArrayWithMissingUri(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'uri\' is missing for Resource');

        Resource::fromArray([
            'name' => 'Test File',
        ]);
    }

    public function testFromArrayWithMissingName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'name\' is missing for Resource');

        Resource::fromArray([
            'uri' => 'https://example.com/file.txt',
        ]);
    }

    public function testFromArrayWithInvalidUriType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'uri\': expected string, got integer');

        Resource::fromArray([
            'uri' => 123,
            'name' => 'Test File',
        ]);
    }

    public function testFromArrayWithInvalidNameType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'name\': expected string, got integer');

        Resource::fromArray([
            'uri' => 'https://example.com/file.txt',
            'name' => 123,
        ]);
    }

    public function testFromArrayWithInvalidDescriptionType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'description\': expected string, got integer');

        Resource::fromArray([
            'uri' => 'https://example.com/file.txt',
            'name' => 'Test File',
            'description' => 123,
        ]);
    }

    public function testFromArrayWithInvalidMimeTypeType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'mimeType\': expected string, got integer');

        Resource::fromArray([
            'uri' => 'https://example.com/file.txt',
            'name' => 'Test File',
            'mimeType' => 123,
        ]);
    }

    public function testFromArrayWithInvalidSizeType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'size\': expected integer, got string');

        Resource::fromArray([
            'uri' => 'https://example.com/file.txt',
            'name' => 'Test File',
            'size' => 'invalid',
        ]);
    }

    public function testSetUriWithInvalidUri(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'uri\' cannot be empty');

        $resource = new Resource('https://example.com/file.txt', 'Test');
        $resource->setUri('');
    }

    public function testSetNameWithEmptyName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'name\' cannot be empty');

        $resource = new Resource('https://example.com/file.txt', 'Test');
        $resource->setName('');
    }

    public function testSetSizeWithNegativeSize(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'size\': cannot be negative');

        $resource = new Resource('https://example.com/file.txt', 'Test');
        $resource->setSize(-1);
    }

    public function testHasMethodsWithData(): void
    {
        $resource = new Resource(
            'file:///valid.txt',
            'Test',
            'Description',
            'text/plain',
            1024,
            new Annotations()
        );

        $this->assertTrue($resource->hasDescription());
        $this->assertTrue($resource->hasMimeType());
        $this->assertTrue($resource->hasSize());
    }

    public function testHasMethodsWithoutData(): void
    {
        $resource = new Resource('file:///valid.txt', 'Test');

        $this->assertFalse($resource->hasDescription());
        $this->assertFalse($resource->hasMimeType());
        $this->assertFalse($resource->hasSize());
    }

    public function testIsTargetedTo(): void
    {
        $resource = new Resource('file:///valid.txt', 'Test');
        $this->assertTrue($resource->isTargetedTo(MessageConstants::ROLE_USER));

        $annotations = new Annotations([MessageConstants::ROLE_USER]);
        $resource->setAnnotations($annotations);
        $this->assertTrue($resource->isTargetedTo(MessageConstants::ROLE_USER));
        $this->assertFalse($resource->isTargetedTo(MessageConstants::ROLE_ASSISTANT));
    }

    public function testGetPriority(): void
    {
        $resource = new Resource('file:///valid.txt', 'Test');
        $this->assertNull($resource->getPriority());

        $annotations = new Annotations(null, 0.8);
        $resource->setAnnotations($annotations);
        $this->assertSame(0.8, $resource->getPriority());
    }

    public function testToArray(): void
    {
        $uri = 'file:///path/to/resource.txt';
        $name = 'Test Resource';
        $description = 'A test resource';
        $mimeType = 'text/plain';
        $size = 1024;
        $annotations = new Annotations([MessageConstants::ROLE_USER], 0.5);

        $resource = new Resource($uri, $name, $description, $mimeType, $size, $annotations);

        $expected = [
            'uri' => $uri,
            'name' => $name,
            'description' => $description,
            'mimeType' => $mimeType,
            'size' => $size,
            'annotations' => [
                'audience' => [MessageConstants::ROLE_USER],
                'priority' => 0.5,
            ],
        ];

        $this->assertSame($expected, $resource->toArray());
    }

    public function testToArrayWithMinimalData(): void
    {
        $resource = new Resource('file:///valid.txt', 'Test');

        $expected = [
            'uri' => 'file:///valid.txt',
            'name' => 'Test',
        ];

        $this->assertSame($expected, $resource->toArray());
    }

    public function testToJson(): void
    {
        $resource = new Resource('file:///valid.txt', 'Test Resource');
        $json = $resource->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('file:///valid.txt', $decoded['uri']);
        $this->assertSame('Test Resource', $decoded['name']);
    }

    public function testWithMethods(): void
    {
        $original = new Resource('file:///original.txt', 'Original');

        $withUri = $original->withUri('file:///new.txt');
        $this->assertNotSame($original, $withUri);
        $this->assertSame('file:///original.txt', $original->getUri());
        $this->assertSame('file:///new.txt', $withUri->getUri());

        $withName = $original->withName('New Name');
        $this->assertNotSame($original, $withName);
        $this->assertSame('Original', $original->getName());
        $this->assertSame('New Name', $withName->getName());

        $withDescription = $original->withDescription('New Description');
        $this->assertNotSame($original, $withDescription);
        $this->assertNull($original->getDescription());
        $this->assertSame('New Description', $withDescription->getDescription());

        $withMimeType = $original->withMimeType('text/plain');
        $this->assertNotSame($original, $withMimeType);
        $this->assertNull($original->getMimeType());
        $this->assertSame('text/plain', $withMimeType->getMimeType());

        $withSize = $original->withSize(1024);
        $this->assertNotSame($original, $withSize);
        $this->assertNull($original->getSize());
        $this->assertSame(1024, $withSize->getSize());

        $annotations = new Annotations();
        $withAnnotations = $original->withAnnotations($annotations);
        $this->assertNotSame($original, $withAnnotations);
        $this->assertNull($original->getAnnotations());
        $this->assertSame($annotations, $withAnnotations->getAnnotations());
    }
}
