<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\FastMcp\Resources;

use Closure;
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Shared\Exceptions\ResourceError;
use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Resources\BlobResourceContents;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RegisteredResource class.
 * @internal
 */
class RegisteredResourceTest extends TestCase
{
    private Resource $sampleResource;

    private Closure $sampleCallable;

    protected function setUp(): void
    {
        $this->sampleResource = new Resource(
            'file:///test.txt',
            'Test File',
            'A test text file',
            'text/plain',
            100
        );

        $this->sampleCallable = function (string $uri): TextResourceContents {
            return new TextResourceContents($uri, 'Hello, World!', 'text/plain');
        };
    }

    public function testConstructor(): void
    {
        $registeredResource = new RegisteredResource($this->sampleResource, $this->sampleCallable);

        $this->assertSame($this->sampleResource, $registeredResource->getResource());
        $this->assertEquals('file:///test.txt', $registeredResource->getUri());
        $this->assertEquals('Test File', $registeredResource->getName());
        $this->assertEquals('A test text file', $registeredResource->getDescription());
    }

    public function testGetContentSuccess(): void
    {
        $registeredResource = new RegisteredResource($this->sampleResource, $this->sampleCallable);
        $content = $registeredResource->getContent();

        $this->assertInstanceOf(TextResourceContents::class, $content);
        $this->assertEquals('file:///test.txt', $content->getUri());
        $this->assertEquals('Hello, World!', $content->getText());
        $this->assertEquals('text/plain', $content->getMimeType());
    }

    public function testGetContentWithCallableException(): void
    {
        $failingCallable = function (string $uri): void {
            throw new Exception('File not found');
        };

        $registeredResource = new RegisteredResource($this->sampleResource, $failingCallable);

        $this->expectException(ResourceError::class);
        $this->expectExceptionMessage('Error accessing resource file:///test.txt: File not found');

        $registeredResource->getContent();
    }

    public function testGetContentWithInvalidReturnType(): void
    {
        $invalidCallable = function (string $uri): string {
            return 'Invalid return type';
        };

        $registeredResource = new RegisteredResource($this->sampleResource, $invalidCallable);

        $this->expectException(ResourceError::class);
        $this->expectExceptionMessage('Resource callable must return ResourceContents instance');

        $registeredResource->getContent();
    }

    public function testGetMimeType(): void
    {
        $registeredResource = new RegisteredResource($this->sampleResource, $this->sampleCallable);
        $this->assertEquals('text/plain', $registeredResource->getMimeType());
    }

    public function testGetSize(): void
    {
        $registeredResource = new RegisteredResource($this->sampleResource, $this->sampleCallable);
        $this->assertEquals(100, $registeredResource->getSize());
    }

    public function testGetAnnotations(): void
    {
        $annotations = new Annotations(
            audience: ['user'],
            priority: 0.8
        );

        $resource = new Resource(
            'file:///annotated.txt',
            'Annotated File',
            'A file with annotations',
            'text/plain',
            null,
            $annotations
        );

        $registeredResource = new RegisteredResource($resource, $this->sampleCallable);

        $this->assertSame($annotations, $registeredResource->getAnnotations());
        $this->assertEquals(0.8, $registeredResource->getAnnotations()->getPriority());
    }

    public function testGetAnnotationsNull(): void
    {
        $registeredResource = new RegisteredResource($this->sampleResource, $this->sampleCallable);
        $this->assertNull($registeredResource->getAnnotations());
    }

    public function testHasDescription(): void
    {
        $registeredResource = new RegisteredResource($this->sampleResource, $this->sampleCallable);
        $this->assertTrue($registeredResource->hasDescription());

        // Test with resource without description
        $resourceNoDesc = new Resource('file:///no-desc.txt', 'No Description File');
        $registeredNoDesc = new RegisteredResource($resourceNoDesc, $this->sampleCallable);
        $this->assertFalse($registeredNoDesc->hasDescription());
    }

    public function testHasMimeType(): void
    {
        $registeredResource = new RegisteredResource($this->sampleResource, $this->sampleCallable);
        $this->assertTrue($registeredResource->hasMimeType());

        // Test with resource without MIME type
        $resourceNoMime = new Resource('file:///no-mime.txt', 'No MIME File');
        $registeredNoMime = new RegisteredResource($resourceNoMime, $this->sampleCallable);
        $this->assertFalse($registeredNoMime->hasMimeType());
    }

    public function testHasSize(): void
    {
        $registeredResource = new RegisteredResource($this->sampleResource, $this->sampleCallable);
        $this->assertTrue($registeredResource->hasSize());

        // Test with resource without size
        $resourceNoSize = new Resource('file:///no-size.txt', 'No Size File', null, null, null);
        $registeredNoSize = new RegisteredResource($resourceNoSize, $this->sampleCallable);
        $this->assertFalse($registeredNoSize->hasSize());
    }

    public function testHasAnnotations(): void
    {
        $registeredResource = new RegisteredResource($this->sampleResource, $this->sampleCallable);
        $this->assertFalse($registeredResource->hasAnnotations());

        // Test with annotations
        $annotations = new Annotations(['user']);
        $resourceWithAnnotations = new Resource(
            'file:///with-annotations.txt',
            'With Annotations',
            null,
            null,
            null,
            $annotations
        );
        $registeredWithAnnotations = new RegisteredResource($resourceWithAnnotations, $this->sampleCallable);
        $this->assertTrue($registeredWithAnnotations->hasAnnotations());
    }

    public function testWithBlobContent(): void
    {
        $blobCallable = function (string $uri): BlobResourceContents {
            return new BlobResourceContents($uri, base64_encode('Binary data'), 'application/octet-stream');
        };

        $blobResource = new Resource(
            'file:///binary.dat',
            'Binary File',
            'A binary file',
            'application/octet-stream',
            1024
        );

        $registeredResource = new RegisteredResource($blobResource, $blobCallable);
        $content = $registeredResource->getContent();

        $this->assertInstanceOf(BlobResourceContents::class, $content);
        $this->assertEquals('file:///binary.dat', $content->getUri());
        $this->assertEquals('application/octet-stream', $content->getMimeType());
        $this->assertTrue($content->isBlob());
        $this->assertFalse($content->isText());
    }

    public function testWithDifferentCallableTypes(): void
    {
        // Test with different callable types
        $callables = [
            // Closure
            function (string $uri): TextResourceContents {
                return new TextResourceContents($uri, 'closure result', 'text/plain');
            },
            // Arrow function
            fn (string $uri) => new TextResourceContents($uri, 'arrow function result', 'text/plain'),
        ];

        foreach ($callables as $callable) {
            $resource = new Resource('file:///test.txt', 'Test File');
            $registeredResource = new RegisteredResource($resource, $callable);

            $this->assertInstanceOf(RegisteredResource::class, $registeredResource);
            $content = $registeredResource->getContent();
            $this->assertInstanceOf(TextResourceContents::class, $content);
        }
    }

    public function testResourceWithComplexUri(): void
    {
        $complexCallable = function (string $uri): TextResourceContents {
            // Parse URI and create appropriate content based on URI
            if (str_contains($uri, 'config')) {
                $content = '{"setting": "value"}';
                $mimeType = 'application/json';
            } elseif (str_contains($uri, 'log')) {
                $content = '[INFO] Application started';
                $mimeType = 'text/plain';
            } else {
                $content = 'Unknown resource';
                $mimeType = 'text/plain';
            }

            return new TextResourceContents($uri, $content, $mimeType);
        };

        $configResource = new Resource(
            'app://config/settings.json',
            'Application Config',
            'Application configuration settings'
        );

        $logResource = new Resource(
            'app://logs/application.log',
            'Application Log',
            'Application log file'
        );

        $registeredConfig = new RegisteredResource($configResource, $complexCallable);
        $registeredLog = new RegisteredResource($logResource, $complexCallable);

        $configContent = $registeredConfig->getContent();
        $this->assertEquals('{"setting": "value"}', $configContent->getText());
        $this->assertEquals('application/json', $configContent->getMimeType());

        $logContent = $registeredLog->getContent();
        $this->assertEquals('[INFO] Application started', $logContent->getText());
        $this->assertEquals('text/plain', $logContent->getMimeType());
    }
}
