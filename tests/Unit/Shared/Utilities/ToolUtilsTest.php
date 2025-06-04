<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Utilities;

use DateTime;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\ToolUtils;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ToolUtilsTest extends TestCase
{
    public function testGenerateInputSchemaWithBasicTypes(): void
    {
        $schema = ToolUtils::generateInputSchema(TestClass::class, 'methodWithBasicTypes');

        $expected = [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Parameter: name',
                ],
                'age' => [
                    'type' => 'integer',
                    'description' => 'Parameter: age',
                ],
                'rate' => [
                    'type' => 'number',
                    'description' => 'Parameter: rate',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Parameter: active',
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Parameter: tags',
                ],
            ],
            'required' => ['name', 'age', 'rate', 'active', 'tags'],
        ];

        $this->assertEquals($expected, $schema);
    }

    public function testGenerateInputSchemaWithOptionalParameters(): void
    {
        $schema = ToolUtils::generateInputSchema(TestClass::class, 'methodWithOptionalParams');

        $expected = [
            'type' => 'object',
            'properties' => [
                'required_param' => [
                    'type' => 'string',
                    'description' => 'Parameter: required_param',
                ],
                'optional_param' => [
                    'type' => 'string',
                    'description' => 'Parameter: optional_param',
                    'default' => 'default_value',
                ],
                'nullable_param' => [
                    'type' => 'string',
                    'description' => 'Parameter: nullable_param',
                ],
            ],
            'required' => ['required_param'],
        ];

        $this->assertEquals($expected, $schema);
    }

    public function testGenerateInputSchemaWithNoTypeHint(): void
    {
        $schema = ToolUtils::generateInputSchema(TestClass::class, 'methodWithNoTypeHint');

        $expected = [
            'type' => 'object',
            'properties' => [
                'param' => [
                    'type' => 'string',
                    'description' => 'Parameter: param (no type hint)',
                    'default' => 'default',
                ],
            ],
        ];

        $this->assertEquals($expected, $schema);
    }

    public function testGenerateInputSchemaWithNoParameters(): void
    {
        $schema = ToolUtils::generateInputSchema(TestClass::class, 'methodWithNoParams');

        $expected = [
            'type' => 'object',
            'properties' => [],
        ];

        $this->assertEquals($expected, $schema);
    }

    public function testGenerateInputSchemaThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("Invalid value for field 'class': Class 'NonExistentClass' does not exist");

        ToolUtils::generateInputSchema('NonExistentClass', 'someMethod');
    }

    public function testGenerateInputSchemaThrowsExceptionForNonExistentMethod(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("Invalid value for field 'method': Method 'nonExistentMethod' does not exist in class");

        ToolUtils::generateInputSchema(TestClass::class, 'nonExistentMethod');
    }

    public function testGenerateInputSchemaThrowsExceptionForEmptyClass(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("Field 'class' cannot be empty");

        ToolUtils::generateInputSchema('', 'someMethod');
    }

    public function testGenerateInputSchemaThrowsExceptionForEmptyMethod(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("Field 'method' cannot be empty");

        ToolUtils::generateInputSchema(TestClass::class, '');
    }

    public function testGenerateInputSchemaThrowsExceptionForCustomClass(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage("has unsupported type 'DateTime'. Only basic types");

        ToolUtils::generateInputSchema(TestClass::class, 'methodWithCustomClass');
    }

    public function testGenerateInputSchemaWithDefaultValues(): void
    {
        $schema = ToolUtils::generateInputSchema(TestClass::class, 'methodWithDefaultValues');

        $expected = [
            'type' => 'object',
            'properties' => [
                'str_with_default' => [
                    'type' => 'string',
                    'description' => 'Parameter: str_with_default',
                    'default' => 'hello',
                ],
                'int_with_default' => [
                    'type' => 'integer',
                    'description' => 'Parameter: int_with_default',
                    'default' => 42,
                ],
                'bool_with_default' => [
                    'type' => 'boolean',
                    'description' => 'Parameter: bool_with_default',
                    'default' => true,
                ],
                'array_with_default' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Parameter: array_with_default',
                    'default' => [],
                ],
            ],
        ];

        $this->assertEquals($expected, $schema);
    }
}

/**
 * Test class for ToolUtils reflection tests.
 */
class TestClass
{
    /**
     * @param array<string> $tags
     */
    public function methodWithBasicTypes(string $name, int $age, float $rate, bool $active, array $tags): void
    {
    }

    public function methodWithOptionalParams(string $required_param, string $optional_param = 'default_value', ?string $nullable_param = null): void
    {
    }

    /**
     * @param mixed $param
     */
    public function methodWithNoTypeHint($param = 'default'): void
    {
    }

    public function methodWithNoParams(): void
    {
    }

    public function methodWithCustomClass(DateTime $date): void
    {
    }

    /**
     * @param array<mixed> $array_with_default
     */
    public function methodWithDefaultValues(string $str_with_default = 'hello', int $int_with_default = 42, bool $bool_with_default = true, array $array_with_default = []): void
    {
    }
}
