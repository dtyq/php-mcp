<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Resources;

use Dtyq\PhpMcp\Types\Content\Annotations;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Resources\ResourceTemplate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ResourceTemplateTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $uriTemplate = 'file:///path/to/{filename}';
        $name = 'File Template';
        $description = 'A template for files';
        $mimeType = 'text/plain';
        $annotations = new Annotations([ProtocolConstants::ROLE_USER], 0.5);

        $template = new ResourceTemplate($uriTemplate, $name, $description, $mimeType, $annotations);

        $this->assertSame($uriTemplate, $template->getUriTemplate());
        $this->assertSame($name, $template->getName());
        $this->assertSame($description, $template->getDescription());
        $this->assertSame($mimeType, $template->getMimeType());
        $this->assertSame($annotations, $template->getAnnotations());
        $this->assertTrue($template->hasAnnotations());
    }

    public function testConstructorWithMinimalData(): void
    {
        $uriTemplate = 'file:///path/to/{filename}';
        $name = 'File Template';

        $template = new ResourceTemplate($uriTemplate, $name);

        $this->assertSame($uriTemplate, $template->getUriTemplate());
        $this->assertSame($name, $template->getName());
        $this->assertNull($template->getDescription());
        $this->assertNull($template->getMimeType());
        $this->assertNull($template->getAnnotations());
        $this->assertFalse($template->hasAnnotations());
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'uriTemplate' => 'file:///path/to/{filename}',
            'name' => 'File Template',
            'description' => 'A template for files',
            'mimeType' => 'text/plain',
            'annotations' => [
                'audience' => [ProtocolConstants::ROLE_USER],
                'priority' => 0.7,
            ],
        ];

        $template = ResourceTemplate::fromArray($data);

        $this->assertSame($data['uriTemplate'], $template->getUriTemplate());
        $this->assertSame($data['name'], $template->getName());
        $this->assertSame($data['description'], $template->getDescription());
        $this->assertSame($data['mimeType'], $template->getMimeType());
        $this->assertTrue($template->hasAnnotations());
        $this->assertSame([ProtocolConstants::ROLE_USER], $template->getAnnotations()->getAudience());
        $this->assertSame(0.7, $template->getAnnotations()->getPriority());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'uriTemplate' => 'file:///path/to/{filename}',
            'name' => 'File Template',
        ];

        $template = ResourceTemplate::fromArray($data);

        $this->assertSame($data['uriTemplate'], $template->getUriTemplate());
        $this->assertSame($data['name'], $template->getName());
        $this->assertNull($template->getDescription());
        $this->assertNull($template->getMimeType());
        $this->assertFalse($template->hasAnnotations());
    }

    public function testFromArrayWithMissingUriTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UriTemplate field is required for ResourceTemplate');

        ResourceTemplate::fromArray([
            'name' => 'File Template',
        ]);
    }

    public function testFromArrayWithMissingName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name field is required for ResourceTemplate');

        ResourceTemplate::fromArray([
            'uriTemplate' => 'file:///path/to/{filename}',
        ]);
    }

    public function testFromArrayWithInvalidUriTemplateType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UriTemplate field must be a string');

        ResourceTemplate::fromArray([
            'uriTemplate' => 123,
            'name' => 'File Template',
        ]);
    }

    public function testFromArrayWithInvalidNameType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name field must be a string');

        ResourceTemplate::fromArray([
            'uriTemplate' => 'file:///path/to/{filename}',
            'name' => 123,
        ]);
    }

    public function testSetUriTemplateWithEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI template cannot be empty');

        $template = new ResourceTemplate('file:///valid/{id}', 'Test');
        $template->setUriTemplate('');
    }

    public function testSetNameWithEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource template name cannot be empty');

        $template = new ResourceTemplate('file:///valid/{id}', 'Test');
        $template->setName('');
    }

    public function testSetDescriptionWithEmptyString(): void
    {
        $template = new ResourceTemplate('file:///valid/{id}', 'Test');
        $template->setDescription('   ');

        $this->assertNull($template->getDescription());
        $this->assertFalse($template->hasDescription());
    }

    public function testHasMethodsWithData(): void
    {
        $template = new ResourceTemplate(
            'file:///valid/{id}',
            'Test',
            'Description',
            'text/plain',
            new Annotations()
        );

        $this->assertTrue($template->hasDescription());
        $this->assertTrue($template->hasMimeType());
    }

    public function testHasMethodsWithoutData(): void
    {
        $template = new ResourceTemplate('file:///valid/{id}', 'Test');

        $this->assertFalse($template->hasDescription());
        $this->assertFalse($template->hasMimeType());
    }

    public function testIsTargetedTo(): void
    {
        $template = new ResourceTemplate('file:///valid/{id}', 'Test');
        $this->assertTrue($template->isTargetedTo(ProtocolConstants::ROLE_USER));

        $annotations = new Annotations([ProtocolConstants::ROLE_USER]);
        $template->setAnnotations($annotations);
        $this->assertTrue($template->isTargetedTo(ProtocolConstants::ROLE_USER));
        $this->assertFalse($template->isTargetedTo(ProtocolConstants::ROLE_ASSISTANT));
    }

    public function testGetPriority(): void
    {
        $template = new ResourceTemplate('file:///valid/{id}', 'Test');
        $this->assertNull($template->getPriority());

        $annotations = new Annotations(null, 0.8);
        $template->setAnnotations($annotations);
        $this->assertSame(0.8, $template->getPriority());
    }

    public function testExpandUri(): void
    {
        $template = new ResourceTemplate('file:///path/{folder}/{filename}.{ext}', 'Test');

        $variables = [
            'folder' => 'documents',
            'filename' => 'test file',
            'ext' => 'txt',
        ];

        $expanded = $template->expandUri($variables);
        $this->assertSame('file:///path/documents/test%20file.txt', $expanded);
    }

    public function testExpandUriWithNoVariables(): void
    {
        $template = new ResourceTemplate('file:///path/static.txt', 'Test');

        $expanded = $template->expandUri([]);
        $this->assertSame('file:///path/static.txt', $expanded);
    }

    public function testGetVariableNames(): void
    {
        $template = new ResourceTemplate('file:///path/{folder}/{filename}.{ext}', 'Test');

        $variables = $template->getVariableNames();
        $this->assertSame(['folder', 'filename', 'ext'], $variables);
    }

    public function testGetVariableNamesWithNoVariables(): void
    {
        $template = new ResourceTemplate('file:///path/static.txt', 'Test');

        $variables = $template->getVariableNames();
        $this->assertSame([], $variables);
    }

    public function testHasVariables(): void
    {
        $templateWithVars = new ResourceTemplate('file:///path/{filename}', 'Test');
        $this->assertTrue($templateWithVars->hasVariables());

        $templateWithoutVars = new ResourceTemplate('file:///path/static.txt', 'Test');
        $this->assertFalse($templateWithoutVars->hasVariables());
    }

    public function testToArray(): void
    {
        $uriTemplate = 'file:///path/{filename}';
        $name = 'File Template';
        $description = 'A template for files';
        $mimeType = 'text/plain';
        $annotations = new Annotations([ProtocolConstants::ROLE_USER], 0.5);

        $template = new ResourceTemplate($uriTemplate, $name, $description, $mimeType, $annotations);

        $expected = [
            'uriTemplate' => $uriTemplate,
            'name' => $name,
            'description' => $description,
            'mimeType' => $mimeType,
            'annotations' => [
                'audience' => [ProtocolConstants::ROLE_USER],
                'priority' => 0.5,
            ],
        ];

        $this->assertSame($expected, $template->toArray());
    }

    public function testToArrayWithMinimalData(): void
    {
        $template = new ResourceTemplate('file:///path/{filename}', 'Test');

        $expected = [
            'uriTemplate' => 'file:///path/{filename}',
            'name' => 'Test',
        ];

        $this->assertSame($expected, $template->toArray());
    }

    public function testToJson(): void
    {
        $template = new ResourceTemplate('file:///path/{filename}', 'Test Template');
        $json = $template->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('file:///path/{filename}', $decoded['uriTemplate']);
        $this->assertSame('Test Template', $decoded['name']);
    }

    public function testWithMethods(): void
    {
        $original = new ResourceTemplate('file:///original/{id}', 'Original');

        $withUriTemplate = $original->withUriTemplate('file:///new/{id}');
        $this->assertNotSame($original, $withUriTemplate);
        $this->assertSame('file:///original/{id}', $original->getUriTemplate());
        $this->assertSame('file:///new/{id}', $withUriTemplate->getUriTemplate());

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

        $annotations = new Annotations();
        $withAnnotations = $original->withAnnotations($annotations);
        $this->assertNotSame($original, $withAnnotations);
        $this->assertNull($original->getAnnotations());
        $this->assertSame($annotations, $withAnnotations->getAnnotations());
    }
}
