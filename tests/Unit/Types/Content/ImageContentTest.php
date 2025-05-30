<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Content;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Content\ContentInterface;
use Dtyq\PhpMcp\Types\Content\ImageContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ImageContent.
 * @internal
 */
class ImageContentTest extends TestCase
{
    /**
     * Test constructor with valid data.
     */
    public function testConstructorWithValidData(): void
    {
        $data = base64_encode('fake image data');
        $mimeType = 'image/png';
        $content = new ImageContent($data, $mimeType);

        $this->assertInstanceOf(ContentInterface::class, $content);
        $this->assertEquals(ProtocolConstants::CONTENT_TYPE_IMAGE, $content->getType());
        $this->assertEquals($data, $content->getData());
        $this->assertEquals($mimeType, $content->getMimeType());
        $this->assertNull($content->getAnnotations());
        $this->assertFalse($content->hasAnnotations());
    }

    /**
     * Test constructor with annotations.
     */
    public function testConstructorWithAnnotations(): void
    {
        $data = base64_encode('fake image data');
        $mimeType = 'image/jpeg';
        $annotations = new Annotations([ProtocolConstants::ROLE_USER], 0.8);
        $content = new ImageContent($data, $mimeType, $annotations);

        $this->assertEquals($data, $content->getData());
        $this->assertEquals($mimeType, $content->getMimeType());
        $this->assertSame($annotations, $content->getAnnotations());
        $this->assertTrue($content->hasAnnotations());
    }

    /**
     * Test constructor with empty data.
     */
    public function testConstructorWithEmptyData(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'data\' cannot be empty');

        new ImageContent('', 'image/png');
    }

    /**
     * Test constructor with invalid MIME type.
     */
    public function testConstructorWithInvalidMimeType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('invalid image MIME type');

        $data = base64_encode('fake image data');
        new ImageContent($data, 'text/plain');
    }

    /**
     * Test setAnnotations method.
     */
    public function testSetAnnotations(): void
    {
        $data = base64_encode('fake image data');
        $content = new ImageContent($data, 'image/png');

        $this->assertFalse($content->hasAnnotations());

        $annotations = new Annotations([ProtocolConstants::ROLE_ASSISTANT], 0.5);
        $content->setAnnotations($annotations);

        $this->assertTrue($content->hasAnnotations());
        $this->assertSame($annotations, $content->getAnnotations());

        $content->setAnnotations(null);
        $this->assertFalse($content->hasAnnotations());
        $this->assertNull($content->getAnnotations());
    }

    /**
     * Test isTargetedTo method.
     */
    public function testIsTargetedTo(): void
    {
        $data = base64_encode('fake image data');
        $content = new ImageContent($data, 'image/png');

        // Without annotations, should return true for any role
        $this->assertTrue($content->isTargetedTo(ProtocolConstants::ROLE_USER));
        $this->assertTrue($content->isTargetedTo(ProtocolConstants::ROLE_ASSISTANT));

        // With annotations
        $annotations = new Annotations([ProtocolConstants::ROLE_USER]);
        $content->setAnnotations($annotations);

        $this->assertTrue($content->isTargetedTo(ProtocolConstants::ROLE_USER));
        $this->assertFalse($content->isTargetedTo(ProtocolConstants::ROLE_ASSISTANT));
    }

    /**
     * Test getPriority method.
     */
    public function testGetPriority(): void
    {
        $data = base64_encode('fake image data');
        $content = new ImageContent($data, 'image/png');

        // Without annotations, should return null
        $this->assertNull($content->getPriority());

        // With annotations
        $annotations = new Annotations([ProtocolConstants::ROLE_USER], 0.7);
        $content->setAnnotations($annotations);

        $this->assertEquals(0.7, $content->getPriority());
    }

    /**
     * Test toArray method.
     */
    public function testToArray(): void
    {
        $data = base64_encode('fake image data');
        $mimeType = 'image/png';
        $content = new ImageContent($data, $mimeType);

        $array = $content->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('mimeType', $array);
        $this->assertEquals(ProtocolConstants::CONTENT_TYPE_IMAGE, $array['type']);
        $this->assertEquals($data, $array['data']);
        $this->assertEquals($mimeType, $array['mimeType']);
        $this->assertArrayNotHasKey('annotations', $array);

        // Test with annotations
        $annotations = new Annotations([ProtocolConstants::ROLE_USER]);
        $content->setAnnotations($annotations);

        $arrayWithAnnotations = $content->toArray();
        $this->assertArrayHasKey('annotations', $arrayWithAnnotations);
    }

    /**
     * Test toJson method.
     */
    public function testToJson(): void
    {
        $data = base64_encode('fake image data');
        $mimeType = 'image/jpeg';
        $content = new ImageContent($data, $mimeType);

        $json = $content->toJson();

        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(ProtocolConstants::CONTENT_TYPE_IMAGE, $decoded['type']);
        $this->assertEquals($data, $decoded['data']);
        $this->assertEquals($mimeType, $decoded['mimeType']);
    }

    /**
     * Test fromArray with valid data.
     */
    public function testFromArrayWithValidData(): void
    {
        $data = base64_encode('fake image data');
        $arrayData = [
            'type' => ProtocolConstants::CONTENT_TYPE_IMAGE,
            'data' => $data,
            'mimeType' => 'image/png',
        ];

        $content = ImageContent::fromArray($arrayData);

        $this->assertInstanceOf(ImageContent::class, $content);
        $this->assertEquals($data, $content->getData());
        $this->assertEquals('image/png', $content->getMimeType());
    }

    /**
     * Test fromArray with invalid type.
     */
    public function testFromArrayWithInvalidType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid content type');

        $arrayData = [
            'type' => ProtocolConstants::CONTENT_TYPE_TEXT,
            'data' => base64_encode('fake image data'),
            'mimeType' => 'image/png',
        ];

        ImageContent::fromArray($arrayData);
    }

    /**
     * Test fromArray with missing data.
     */
    public function testFromArrayWithMissingData(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'data\' is missing for ImageContent');

        $arrayData = [
            'type' => ProtocolConstants::CONTENT_TYPE_IMAGE,
            'mimeType' => 'image/png',
        ];

        ImageContent::fromArray($arrayData);
    }

    /**
     * Test fromArray with missing MIME type.
     */
    public function testFromArrayWithMissingMimeType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'mimeType\' is missing for ImageContent');

        $arrayData = [
            'type' => ProtocolConstants::CONTENT_TYPE_IMAGE,
            'data' => base64_encode('fake image data'),
        ];

        ImageContent::fromArray($arrayData);
    }
}
