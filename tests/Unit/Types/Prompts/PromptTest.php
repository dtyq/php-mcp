<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Prompts;

use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class PromptTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $name = 'analyze-code';
        $description = 'Analyze code for improvements';
        $arguments = [
            new PromptArgument('language', 'Programming language', true),
            new PromptArgument('code', 'Code to analyze', true),
        ];

        $prompt = new Prompt($name, $description, $arguments);

        $this->assertSame($name, $prompt->getName());
        $this->assertSame($description, $prompt->getDescription());
        $this->assertSame($arguments, $prompt->getArguments());
        $this->assertTrue($prompt->hasDescription());
        $this->assertTrue($prompt->hasArguments());
        $this->assertSame(2, $prompt->getArgumentCount());
    }

    public function testConstructorWithMinimalData(): void
    {
        $name = 'simple-prompt';

        $prompt = new Prompt($name);

        $this->assertSame($name, $prompt->getName());
        $this->assertNull($prompt->getDescription());
        $this->assertSame([], $prompt->getArguments());
        $this->assertFalse($prompt->hasDescription());
        $this->assertFalse($prompt->hasArguments());
        $this->assertSame(0, $prompt->getArgumentCount());
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'name' => 'git-commit',
            'description' => 'Generate Git commit message',
            'arguments' => [
                [
                    'name' => 'changes',
                    'description' => 'Git diff or description',
                    'required' => true,
                ],
                [
                    'name' => 'style',
                    'description' => 'Commit message style',
                    'required' => false,
                ],
            ],
        ];

        $prompt = Prompt::fromArray($data);

        $this->assertSame($data['name'], $prompt->getName());
        $this->assertSame($data['description'], $prompt->getDescription());
        $this->assertSame(2, $prompt->getArgumentCount());

        $arguments = $prompt->getArguments();
        $this->assertSame('changes', $arguments[0]->getName());
        $this->assertTrue($arguments[0]->isRequired());
        $this->assertSame('style', $arguments[1]->getName());
        $this->assertFalse($arguments[1]->isRequired());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'name' => 'simple',
        ];

        $prompt = Prompt::fromArray($data);

        $this->assertSame($data['name'], $prompt->getName());
        $this->assertNull($prompt->getDescription());
        $this->assertSame([], $prompt->getArguments());
    }

    public function testFromArrayWithMissingName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'name\' is missing for Prompt');

        Prompt::fromArray([
            'description' => 'Some description',
        ]);
    }

    public function testFromArrayWithInvalidNameType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'name\': expected string, got integer');

        Prompt::fromArray([
            'name' => 123,
        ]);
    }

    public function testFromArrayWithInvalidDescriptionType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'description\': expected string, got integer');

        Prompt::fromArray([
            'name' => 'test',
            'description' => 123,
        ]);
    }

    public function testFromArrayWithInvalidArgumentsType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'arguments\': expected array, got string');

        Prompt::fromArray([
            'name' => 'test',
            'arguments' => 'invalid',
        ]);
    }

    public function testFromArrayWithInvalidArgumentType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'arguments[0]\': expected array, got string');

        Prompt::fromArray([
            'name' => 'test',
            'arguments' => ['invalid'],
        ]);
    }

    public function testSetNameWithEmptyString(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'name\' cannot be empty');

        $prompt = new Prompt('test');
        $prompt->setName('');
    }

    public function testSetArgumentsWithInvalidType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'arguments[0]\': expected PromptArgument, got string');

        $prompt = new Prompt('test');
        $prompt->setArguments(['invalid']);
    }

    public function testAddArgument(): void
    {
        $prompt = new Prompt('test');
        $argument = new PromptArgument('param', 'Parameter');

        $this->assertFalse($prompt->hasArguments());

        $prompt->addArgument($argument);

        $this->assertTrue($prompt->hasArguments());
        $this->assertSame(1, $prompt->getArgumentCount());
        $this->assertSame($argument, $prompt->getArguments()[0]);
    }

    public function testRemoveArgument(): void
    {
        $arg1 = new PromptArgument('param1');
        $arg2 = new PromptArgument('param2');
        $prompt = new Prompt('test', null, [$arg1, $arg2]);

        $this->assertSame(2, $prompt->getArgumentCount());

        $result = $prompt->removeArgument('param1');
        $this->assertTrue($result);
        $this->assertSame(1, $prompt->getArgumentCount());
        $this->assertSame('param2', $prompt->getArguments()[0]->getName());

        $result = $prompt->removeArgument('nonexistent');
        $this->assertFalse($result);
        $this->assertSame(1, $prompt->getArgumentCount());
    }

    public function testGetArgument(): void
    {
        $arg1 = new PromptArgument('param1');
        $arg2 = new PromptArgument('param2');
        $prompt = new Prompt('test', null, [$arg1, $arg2]);

        $found = $prompt->getArgument('param1');
        $this->assertSame($arg1, $found);

        $notFound = $prompt->getArgument('nonexistent');
        $this->assertNull($notFound);
    }

    public function testGetRequiredArguments(): void
    {
        $required = new PromptArgument('required', null, true);
        $optional = new PromptArgument('optional', null, false);
        $prompt = new Prompt('test', null, [$required, $optional]);

        $requiredArgs = $prompt->getRequiredArguments();
        $this->assertCount(1, $requiredArgs);
        $this->assertSame($required, $requiredArgs[0]);
    }

    public function testGetOptionalArguments(): void
    {
        $required = new PromptArgument('required', null, true);
        $optional = new PromptArgument('optional', null, false);
        $prompt = new Prompt('test', null, [$required, $optional]);

        $optionalArgs = $prompt->getOptionalArguments();
        $this->assertCount(1, $optionalArgs);
        $this->assertSame($optional, array_values($optionalArgs)[0]);
    }

    public function testValidateArgumentsWithValidData(): void
    {
        $required = new PromptArgument('required', null, true);
        $optional = new PromptArgument('optional', null, false);
        $prompt = new Prompt('test', null, [$required, $optional]);

        $providedArgs = [
            'required' => 'value',
            'optional' => 'value',
        ];

        $this->expectNotToPerformAssertions();
        $prompt->validateArguments($providedArgs);
    }

    public function testValidateArgumentsWithMissingRequired(): void
    {
        $required = new PromptArgument('required', null, true);
        $prompt = new Prompt('test', null, [$required]);

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required argument \'required\' is missing');

        $prompt->validateArguments([]);
    }

    public function testValidateArgumentsWithUnknownArgument(): void
    {
        $prompt = new Prompt('test', null, [new PromptArgument('known')]);

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'arguments\': unknown argument \'unknown\'');

        $prompt->validateArguments(['unknown' => 'value']);
    }

    public function testToArray(): void
    {
        $name = 'test-prompt';
        $description = 'Test prompt';
        $arguments = [
            new PromptArgument('param1', 'Parameter 1', true),
            new PromptArgument('param2', 'Parameter 2', false),
        ];

        $prompt = new Prompt($name, $description, $arguments);

        $expected = [
            'name' => $name,
            'description' => $description,
            'arguments' => [
                [
                    'name' => 'param1',
                    'required' => true,
                    'description' => 'Parameter 1',
                ],
                [
                    'name' => 'param2',
                    'required' => false,
                    'description' => 'Parameter 2',
                ],
            ],
        ];

        $this->assertSame($expected, $prompt->toArray());
    }

    public function testToArrayWithMinimalData(): void
    {
        $prompt = new Prompt('simple');

        $expected = [
            'name' => 'simple',
        ];

        $this->assertSame($expected, $prompt->toArray());
    }

    public function testToJson(): void
    {
        $prompt = new Prompt('test', 'Test prompt');
        $json = $prompt->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('test', $decoded['name']);
        $this->assertSame('Test prompt', $decoded['description']);
    }

    public function testWithMethods(): void
    {
        $original = new Prompt('original', 'Original description');

        $withName = $original->withName('new_name');
        $this->assertNotSame($original, $withName);
        $this->assertSame('original', $original->getName());
        $this->assertSame('new_name', $withName->getName());

        $withDescription = $original->withDescription('New description');
        $this->assertNotSame($original, $withDescription);
        $this->assertSame('Original description', $original->getDescription());
        $this->assertSame('New description', $withDescription->getDescription());

        $newArgs = [new PromptArgument('param')];
        $withArguments = $original->withArguments($newArgs);
        $this->assertNotSame($original, $withArguments);
        $this->assertSame([], $original->getArguments());
        $this->assertSame($newArgs, $withArguments->getArguments());
    }
} 