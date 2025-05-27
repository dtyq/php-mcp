<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Tools;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Dtyq\PhpMcp\Types\Tools\ToolAnnotations;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ToolTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $name = 'test_tool';
        $inputSchema = [
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string'],
            ],
            'required' => ['message'],
        ];
        $description = 'A test tool';
        $annotations = new ToolAnnotations('Test Tool');

        $tool = new Tool($name, $inputSchema, $description, $annotations);

        $this->assertSame($name, $tool->getName());
        $this->assertSame($inputSchema, $tool->getInputSchema());
        $this->assertSame($description, $tool->getDescription());
        $this->assertSame($annotations, $tool->getAnnotations());
        $this->assertTrue($tool->hasDescription());
        $this->assertTrue($tool->hasAnnotations());
    }

    public function testConstructorWithMinimalData(): void
    {
        $name = 'test_tool';
        $inputSchema = ['type' => 'object'];

        $tool = new Tool($name, $inputSchema);

        $this->assertSame($name, $tool->getName());
        $this->assertSame($inputSchema, $tool->getInputSchema());
        $this->assertNull($tool->getDescription());
        $this->assertNull($tool->getAnnotations());
        $this->assertFalse($tool->hasDescription());
        $this->assertFalse($tool->hasAnnotations());
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'name' => 'test_tool',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                ],
            ],
            'description' => 'A test tool',
            'annotations' => [
                'title' => 'Test Tool',
                'readOnlyHint' => true,
            ],
        ];

        $tool = Tool::fromArray($data);

        $this->assertSame($data['name'], $tool->getName());
        $this->assertSame($data['inputSchema'], $tool->getInputSchema());
        $this->assertSame($data['description'], $tool->getDescription());
        $this->assertTrue($tool->hasAnnotations());
        $this->assertSame('Test Tool', $tool->getAnnotations()->getTitle());
        $this->assertTrue($tool->getAnnotations()->getReadOnlyHint());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'name' => 'test_tool',
            'inputSchema' => ['type' => 'object'],
        ];

        $tool = Tool::fromArray($data);

        $this->assertSame($data['name'], $tool->getName());
        $this->assertSame($data['inputSchema'], $tool->getInputSchema());
        $this->assertNull($tool->getDescription());
        $this->assertFalse($tool->hasAnnotations());
    }

    public function testFromArrayWithMissingName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'name\' is missing for Tool');

        Tool::fromArray([
            'inputSchema' => ['type' => 'object'],
        ]);
    }

    public function testFromArrayWithMissingInputSchema(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'inputSchema\' is missing for Tool');

        Tool::fromArray([
            'name' => 'test_tool',
        ]);
    }

    public function testFromArrayWithInvalidNameType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'name\': expected string, got integer');

        Tool::fromArray([
            'name' => 123,
            'inputSchema' => ['type' => 'object'],
        ]);
    }

    public function testFromArrayWithInvalidInputSchemaType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'inputSchema\': expected array, got string');

        Tool::fromArray([
            'name' => 'test_tool',
            'inputSchema' => 'invalid',
        ]);
    }

    public function testFromArrayWithInvalidDescriptionType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'description\': expected string, got integer');

        Tool::fromArray([
            'name' => 'test_tool',
            'inputSchema' => ['type' => 'object'],
            'description' => 123,
        ]);
    }

    public function testSetNameWithEmptyName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'name\' cannot be empty');

        $tool = new Tool('test', ['type' => 'object']);
        $tool->setName('');
    }

    public function testSetInputSchemaWithEmptySchema(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'inputSchema\' cannot be empty');

        $tool = new Tool('test', ['type' => 'object']);
        $tool->setInputSchema([]);
    }

    public function testSetDescriptionWithEmptyString(): void
    {
        $tool = new Tool('test', ['type' => 'object']);
        $tool->setDescription('   ');

        $this->assertNull($tool->getDescription());
        $this->assertFalse($tool->hasDescription());
    }

    public function testGetTitleFromAnnotations(): void
    {
        $annotations = new ToolAnnotations('Custom Title');
        $tool = new Tool('test_tool', ['type' => 'object'], null, $annotations);

        $this->assertSame('Custom Title', $tool->getTitle());
    }

    public function testGetTitleFromName(): void
    {
        $tool = new Tool('test_tool', ['type' => 'object']);

        $this->assertSame('test_tool', $tool->getTitle());
    }

    public function testBehaviorMethodsWithoutAnnotations(): void
    {
        $tool = new Tool('test', ['type' => 'object']);

        $this->assertFalse($tool->isReadOnly());
        $this->assertTrue($tool->isDestructive());
        $this->assertFalse($tool->isIdempotent());
        $this->assertTrue($tool->isOpenWorld());
    }

    public function testBehaviorMethodsWithAnnotations(): void
    {
        $annotations = new ToolAnnotations(null, true, false, true, false);
        $tool = new Tool('test', ['type' => 'object'], null, $annotations);

        $this->assertTrue($tool->isReadOnly());
        $this->assertFalse($tool->isDestructive());
        $this->assertTrue($tool->isIdempotent());
        $this->assertFalse($tool->isOpenWorld());
    }

    public function testValidateArgumentsWithValidData(): void
    {
        $inputSchema = [
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string'],
                'count' => ['type' => 'integer'],
            ],
            'required' => ['message'],
        ];

        $tool = new Tool('test', $inputSchema);

        $arguments = [
            'message' => 'Hello',
            'count' => 5,
        ];

        $this->assertTrue($tool->validateArguments($arguments));
    }

    public function testValidateArgumentsWithMissingRequired(): void
    {
        $inputSchema = [
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string'],
            ],
            'required' => ['message'],
        ];

        $tool = new Tool('test', $inputSchema);

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required argument \'message\' is missing');

        $tool->validateArguments([]);
    }

    public function testValidateArgumentsWithWrongType(): void
    {
        $inputSchema = [
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string'],
            ],
        ];

        $tool = new Tool('test', $inputSchema);

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Argument \'message\' must be a string, integer given');

        $tool->validateArguments(['message' => 123]);
    }

    public function testValidateArgumentsWithDifferentTypes(): void
    {
        $inputSchema = [
            'type' => 'object',
            'properties' => [
                'str' => ['type' => 'string'],
                'int' => ['type' => 'integer'],
                'num' => ['type' => 'number'],
                'bool' => ['type' => 'boolean'],
                'arr' => ['type' => 'array'],
                'obj' => ['type' => 'object'],
            ],
        ];

        $tool = new Tool('test', $inputSchema);

        $arguments = [
            'str' => 'hello',
            'int' => 42,
            'num' => 3.14,
            'bool' => true,
            'arr' => [1, 2, 3],
            'obj' => ['key' => 'value'],
        ];

        $this->assertTrue($tool->validateArguments($arguments));
    }

    public function testToArray(): void
    {
        $name = 'test_tool';
        $inputSchema = ['type' => 'object'];
        $description = 'A test tool';
        $annotations = new ToolAnnotations('Test Tool');

        $tool = new Tool($name, $inputSchema, $description, $annotations);

        $expected = [
            'name' => $name,
            'inputSchema' => $inputSchema,
            'description' => $description,
            'annotations' => $annotations->toArray(),
        ];

        $this->assertSame($expected, $tool->toArray());
    }

    public function testToArrayWithMinimalData(): void
    {
        $tool = new Tool('test', ['type' => 'object']);

        $expected = [
            'name' => 'test',
            'inputSchema' => ['type' => 'object'],
        ];

        $this->assertSame($expected, $tool->toArray());
    }

    public function testToJson(): void
    {
        $tool = new Tool('test_tool', ['type' => 'object'], 'Test description');
        $json = $tool->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('test_tool', $decoded['name']);
        $this->assertSame(['type' => 'object'], $decoded['inputSchema']);
        $this->assertSame('Test description', $decoded['description']);
    }

    public function testWithMethods(): void
    {
        $original = new Tool('original', ['type' => 'object']);

        $withName = $original->withName('new_name');
        $this->assertNotSame($original, $withName);
        $this->assertSame('original', $original->getName());
        $this->assertSame('new_name', $withName->getName());

        $newSchema = ['type' => 'object', 'properties' => []];
        $withSchema = $original->withInputSchema($newSchema);
        $this->assertNotSame($original, $withSchema);
        $this->assertSame(['type' => 'object'], $original->getInputSchema());
        $this->assertSame($newSchema, $withSchema->getInputSchema());

        $withDescription = $original->withDescription('New description');
        $this->assertNotSame($original, $withDescription);
        $this->assertNull($original->getDescription());
        $this->assertSame('New description', $withDescription->getDescription());

        $annotations = new ToolAnnotations('Title');
        $withAnnotations = $original->withAnnotations($annotations);
        $this->assertNotSame($original, $withAnnotations);
        $this->assertNull($original->getAnnotations());
        $this->assertSame($annotations, $withAnnotations->getAnnotations());
    }
}
