<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Prompts;

use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class PromptArgumentTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $name = 'language';
        $description = 'Programming language';
        $required = true;

        $argument = new PromptArgument($name, $description, $required);

        $this->assertSame($name, $argument->getName());
        $this->assertSame($description, $argument->getDescription());
        $this->assertTrue($argument->isRequired());
        $this->assertTrue($argument->hasDescription());
    }

    public function testConstructorWithMinimalData(): void
    {
        $name = 'optional_param';

        $argument = new PromptArgument($name);

        $this->assertSame($name, $argument->getName());
        $this->assertNull($argument->getDescription());
        $this->assertFalse($argument->isRequired());
        $this->assertFalse($argument->hasDescription());
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'name' => 'code',
            'description' => 'Code to analyze',
            'required' => true,
        ];

        $argument = PromptArgument::fromArray($data);

        $this->assertSame($data['name'], $argument->getName());
        $this->assertSame($data['description'], $argument->getDescription());
        $this->assertTrue($argument->isRequired());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'name' => 'param',
        ];

        $argument = PromptArgument::fromArray($data);

        $this->assertSame($data['name'], $argument->getName());
        $this->assertNull($argument->getDescription());
        $this->assertFalse($argument->isRequired());
    }

    public function testFromArrayWithMissingName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'name\' is missing for PromptArgument');

        PromptArgument::fromArray([
            'description' => 'Some description',
        ]);
    }

    public function testFromArrayWithInvalidNameType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'name\': expected string, got integer');

        PromptArgument::fromArray([
            'name' => 123,
        ]);
    }

    public function testFromArrayWithInvalidDescriptionType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'description\': expected string, got integer');

        PromptArgument::fromArray([
            'name' => 'param',
            'description' => 123,
        ]);
    }

    public function testFromArrayWithInvalidRequiredType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'required\': expected boolean, got string');

        PromptArgument::fromArray([
            'name' => 'param',
            'required' => 'true',
        ]);
    }

    public function testSetNameWithEmptyString(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'name\' cannot be empty');

        $argument = new PromptArgument('test');
        $argument->setName('');
    }

    public function testSetNameWithWhitespaceOnly(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'name\' cannot be empty');

        $argument = new PromptArgument('test');
        $argument->setName('   ');
    }

    public function testSetDescriptionWithEmptyString(): void
    {
        $argument = new PromptArgument('test');
        $argument->setDescription('   ');

        $this->assertNull($argument->getDescription());
        $this->assertFalse($argument->hasDescription());
    }

    public function testSetDescriptionWithValidString(): void
    {
        $argument = new PromptArgument('test');
        $description = 'Valid description';
        $argument->setDescription($description);

        $this->assertSame($description, $argument->getDescription());
        $this->assertTrue($argument->hasDescription());
    }

    public function testSetRequired(): void
    {
        $argument = new PromptArgument('test');

        $this->assertFalse($argument->isRequired());

        $argument->setRequired(true);
        $this->assertTrue($argument->isRequired());

        $argument->setRequired(false);
        $this->assertFalse($argument->isRequired());
    }

    public function testToArray(): void
    {
        $name = 'language';
        $description = 'Programming language';
        $required = true;

        $argument = new PromptArgument($name, $description, $required);

        $expected = [
            'name' => $name,
            'required' => $required,
            'description' => $description,
        ];

        $this->assertSame($expected, $argument->toArray());
    }

    public function testToArrayWithMinimalData(): void
    {
        $argument = new PromptArgument('param');

        $expected = [
            'name' => 'param',
            'required' => false,
        ];

        $this->assertSame($expected, $argument->toArray());
    }

    public function testToJson(): void
    {
        $argument = new PromptArgument('test', 'Test parameter', true);
        $json = $argument->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('test', $decoded['name']);
        $this->assertSame('Test parameter', $decoded['description']);
        $this->assertTrue($decoded['required']);
    }

    public function testWithMethods(): void
    {
        $original = new PromptArgument('original', 'Original description', false);

        $withName = $original->withName('new_name');
        $this->assertNotSame($original, $withName);
        $this->assertSame('original', $original->getName());
        $this->assertSame('new_name', $withName->getName());

        $withDescription = $original->withDescription('New description');
        $this->assertNotSame($original, $withDescription);
        $this->assertSame('Original description', $original->getDescription());
        $this->assertSame('New description', $withDescription->getDescription());

        $withRequired = $original->withRequired(true);
        $this->assertNotSame($original, $withRequired);
        $this->assertFalse($original->isRequired());
        $this->assertTrue($withRequired->isRequired());
    }

    public function testWithDescriptionNull(): void
    {
        $original = new PromptArgument('test', 'Description');
        $withNull = $original->withDescription(null);

        $this->assertNotSame($original, $withNull);
        $this->assertSame('Description', $original->getDescription());
        $this->assertNull($withNull->getDescription());
    }
} 