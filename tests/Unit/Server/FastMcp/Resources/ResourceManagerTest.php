<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\FastMcp\Resources;

use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Server\FastMcp\Resources\ResourceManager;
use Dtyq\PhpMcp\Shared\Exceptions\ResourceError;
use Dtyq\PhpMcp\Types\Resources\BlobResourceContents;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ResourceManager class.
 * @internal
 */
class ResourceManagerTest extends TestCase
{
    private ResourceManager $resourceManager;

    private RegisteredResource $sampleResource;

    protected function setUp(): void
    {
        $this->resourceManager = new ResourceManager();

        $callable = function (string $uri): TextResourceContents {
            return new TextResourceContents($uri, 'Hello, World!', 'text/plain');
        };

        $resource = new Resource(
            'file:///test.txt',
            'Test File',
            'A test text file',
            'text/plain',
            100
        );
        $this->sampleResource = new RegisteredResource($resource, $callable);
    }

    public function testRegisterResource(): void
    {
        $this->resourceManager->register($this->sampleResource);

        $this->assertTrue($this->resourceManager->has('file:///test.txt'));
        $this->assertEquals(1, $this->resourceManager->count());
        $this->assertContains('file:///test.txt', $this->resourceManager->getUris());
    }

    public function testGetResource(): void
    {
        $this->resourceManager->register($this->sampleResource);

        $retrievedResource = $this->resourceManager->get('file:///test.txt');
        $this->assertSame($this->sampleResource, $retrievedResource);
    }

    public function testGetNonexistentResource(): void
    {
        $retrievedResource = $this->resourceManager->get('file:///nonexistent.txt');
        $this->assertNull($retrievedResource);
    }

    public function testHasResource(): void
    {
        $this->assertFalse($this->resourceManager->has('file:///test.txt'));

        $this->resourceManager->register($this->sampleResource);
        $this->assertTrue($this->resourceManager->has('file:///test.txt'));
    }

    public function testRemoveResource(): void
    {
        $this->resourceManager->register($this->sampleResource);
        $this->assertTrue($this->resourceManager->has('file:///test.txt'));

        $result = $this->resourceManager->remove('file:///test.txt');
        $this->assertTrue($result);
        $this->assertFalse($this->resourceManager->has('file:///test.txt'));

        // Try to remove again
        $result = $this->resourceManager->remove('file:///test.txt');
        $this->assertFalse($result);
    }

    public function testGetUris(): void
    {
        $this->assertEquals([], $this->resourceManager->getUris());

        $this->resourceManager->register($this->sampleResource);
        $this->assertEquals(['file:///test.txt'], $this->resourceManager->getUris());

        // Add another resource
        $configResource = new RegisteredResource(
            new Resource('app://config.json', 'Config File'),
            fn (string $uri) => new TextResourceContents($uri, '{}', 'application/json')
        );
        $this->resourceManager->register($configResource);

        $uris = $this->resourceManager->getUris();
        $this->assertCount(2, $uris);
        $this->assertContains('file:///test.txt', $uris);
        $this->assertContains('app://config.json', $uris);
    }

    public function testGetAll(): void
    {
        $this->assertEquals([], $this->resourceManager->getAll());

        $this->resourceManager->register($this->sampleResource);
        $resources = $this->resourceManager->getAll();

        $this->assertCount(1, $resources);
        $this->assertSame($this->sampleResource, $resources[0]);
    }

    public function testCount(): void
    {
        $this->assertEquals(0, $this->resourceManager->count());

        $this->resourceManager->register($this->sampleResource);
        $this->assertEquals(1, $this->resourceManager->count());

        $this->resourceManager->remove('file:///test.txt');
        $this->assertEquals(0, $this->resourceManager->count());
    }

    public function testClear(): void
    {
        $this->resourceManager->register($this->sampleResource);
        $this->assertEquals(1, $this->resourceManager->count());

        $this->resourceManager->clear();
        $this->assertEquals(0, $this->resourceManager->count());
        $this->assertFalse($this->resourceManager->has('file:///test.txt'));
    }

    public function testGetContentSuccess(): void
    {
        $this->resourceManager->register($this->sampleResource);

        $content = $this->resourceManager->getContent('file:///test.txt');

        $this->assertInstanceOf(TextResourceContents::class, $content);
        $this->assertEquals('file:///test.txt', $content->getUri());
        $this->assertEquals('Hello, World!', $content->getText());
        $this->assertEquals('text/plain', $content->getMimeType());
    }

    public function testGetContentUnknownResource(): void
    {
        $this->expectException(ResourceError::class);
        $this->expectExceptionMessage('Unknown resource: file:///nonexistent.txt');

        $this->resourceManager->getContent('file:///nonexistent.txt');
    }

    public function testRegisterMultipleResources(): void
    {
        $configResource = new RegisteredResource(
            new Resource('app://config.json', 'Config File', 'Application configuration'),
            function (string $uri): TextResourceContents {
                return new TextResourceContents($uri, '{"debug": true}', 'application/json');
            }
        );

        $logResource = new RegisteredResource(
            new Resource('app://logs/app.log', 'Application Log', 'Main application log'),
            function (string $uri): TextResourceContents {
                return new TextResourceContents($uri, '[INFO] Server started', 'text/plain');
            }
        );

        $this->resourceManager->register($this->sampleResource);
        $this->resourceManager->register($configResource);
        $this->resourceManager->register($logResource);

        $this->assertEquals(3, $this->resourceManager->count());
        $this->assertTrue($this->resourceManager->has('file:///test.txt'));
        $this->assertTrue($this->resourceManager->has('app://config.json'));
        $this->assertTrue($this->resourceManager->has('app://logs/app.log'));

        // Test content retrieval from each resource
        $testContent = $this->resourceManager->getContent('file:///test.txt');
        $this->assertEquals('Hello, World!', $testContent->getText());

        $configContent = $this->resourceManager->getContent('app://config.json');
        $this->assertEquals('{"debug": true}', $configContent->getText());

        $logContent = $this->resourceManager->getContent('app://logs/app.log');
        $this->assertEquals('[INFO] Server started', $logContent->getText());
    }

    public function testRegisterOverwritesResource(): void
    {
        $this->resourceManager->register($this->sampleResource);
        $this->assertEquals(1, $this->resourceManager->count());

        // Create a different resource with the same URI
        $newResource = new RegisteredResource(
            new Resource('file:///test.txt', 'Updated Test File'),
            fn (string $uri) => new TextResourceContents($uri, 'Updated content', 'text/plain')
        );

        $this->resourceManager->register($newResource);
        $this->assertEquals(1, $this->resourceManager->count()); // Still only one resource

        $retrievedResource = $this->resourceManager->get('file:///test.txt');
        $this->assertSame($newResource, $retrievedResource);
        $this->assertNotSame($this->sampleResource, $retrievedResource);

        // Verify the content is from the new resource
        $content = $this->resourceManager->getContent('file:///test.txt');
        $this->assertEquals('Updated content', $content->getText());
    }

    public function testResourceWithBinaryContent(): void
    {
        $binaryResource = new RegisteredResource(
            new Resource(
                'file:///binary.dat',
                'Binary File',
                'A binary file',
                'application/octet-stream',
                1024
            ),
            function (string $uri): BlobResourceContents {
                return new BlobResourceContents($uri, base64_encode('Binary data'), 'application/octet-stream');
            }
        );

        $this->resourceManager->register($binaryResource);

        $content = $this->resourceManager->getContent('file:///binary.dat');

        $this->assertInstanceOf(BlobResourceContents::class, $content);
        $this->assertEquals('file:///binary.dat', $content->getUri());
        $this->assertEquals('application/octet-stream', $content->getMimeType());
        $this->assertTrue($content->isBlob());
        $this->assertFalse($content->isText());
    }

    public function testFindResourcesByPattern(): void
    {
        // Register multiple resources with different URI patterns
        $resources = [
            new RegisteredResource(
                new Resource('file:///docs/readme.txt', 'README'),
                fn ($uri) => new TextResourceContents($uri, 'README content', 'text/plain')
            ),
            new RegisteredResource(
                new Resource('file:///docs/guide.md', 'User Guide'),
                fn ($uri) => new TextResourceContents($uri, 'Guide content', 'text/markdown')
            ),
            new RegisteredResource(
                new Resource('app://config/database.json', 'DB Config'),
                fn ($uri) => new TextResourceContents($uri, '{}', 'application/json')
            ),
            new RegisteredResource(
                new Resource('app://config/app.json', 'App Config'),
                fn ($uri) => new TextResourceContents($uri, '{}', 'application/json')
            ),
        ];

        foreach ($resources as $resource) {
            $this->resourceManager->register($resource);
        }

        // Test finding by pattern (this would be a custom method in a real implementation)
        $allUris = $this->resourceManager->getUris();

        // Find file:// URIs
        $fileUris = array_filter($allUris, fn ($uri) => str_starts_with($uri, 'file://'));
        $this->assertCount(2, $fileUris);

        // Find app://config URIs
        $configUris = array_filter($allUris, fn ($uri) => str_starts_with($uri, 'app://config'));
        $this->assertCount(2, $configUris);

        // Find .json files
        $jsonUris = array_filter($allUris, fn ($uri) => str_ends_with($uri, '.json'));
        $this->assertCount(2, $jsonUris);
    }

    public function testGetResourceMetadata(): void
    {
        $resourceWithMetadata = new RegisteredResource(
            new Resource(
                'file:///metadata.txt',
                'Metadata File',
                'A file with complete metadata',
                'text/plain',
                256
            ),
            fn ($uri) => new TextResourceContents($uri, 'Content with metadata', 'text/plain')
        );

        $this->resourceManager->register($resourceWithMetadata);

        $resource = $this->resourceManager->get('file:///metadata.txt');
        $this->assertEquals('Metadata File', $resource->getName());
        $this->assertEquals('A file with complete metadata', $resource->getDescription());
        $this->assertEquals('text/plain', $resource->getMimeType());
        $this->assertEquals(256, $resource->getSize());
        $this->assertTrue($resource->hasDescription());
        $this->assertTrue($resource->hasMimeType());
        $this->assertTrue($resource->hasSize());
    }
}
