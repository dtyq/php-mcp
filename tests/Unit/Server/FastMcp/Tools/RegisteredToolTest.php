<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\FastMcp\Tools;

use Closure;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Shared\Exceptions\ToolError;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Dtyq\PhpMcp\Types\Tools\ToolAnnotations;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RegisteredTool class.
 * @internal
 */
class RegisteredToolTest extends TestCase
{
    private Tool $sampleTool;

    private Closure $sampleCallable;

    protected function setUp(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'a' => ['type' => 'integer'],
                'b' => ['type' => 'integer'],
            ],
            'required' => ['a', 'b'],
        ];

        $this->sampleTool = new Tool('add', $schema, 'Add two numbers');
        $this->sampleCallable = function (array $args): int {
            return $args['a'] + $args['b'];
        };
    }

    public function testConstructor(): void
    {
        $registeredTool = new RegisteredTool($this->sampleTool, $this->sampleCallable);

        $this->assertSame($this->sampleTool, $registeredTool->getTool());
        $this->assertEquals('add', $registeredTool->getName());
        $this->assertEquals('Add two numbers', $registeredTool->getDescription());
    }

    public function testExecuteSuccess(): void
    {
        $registeredTool = new RegisteredTool($this->sampleTool, $this->sampleCallable);
        $result = $registeredTool->execute(['a' => 5, 'b' => 3]);

        $this->assertEquals(8, $result);
    }

    public function testExecuteWithValidationFailure(): void
    {
        $registeredTool = new RegisteredTool($this->sampleTool, $this->sampleCallable);

        $this->expectException(ToolError::class);
        $this->expectExceptionMessage('Error executing tool add: Required argument \'b\' is missing');

        // Missing required parameter 'b'
        $registeredTool->execute(['a' => 5]);
    }

    public function testExecuteWithCallableException(): void
    {
        $failingCallable = function (array $args): void {
            throw new Exception('Callable failed');
        };

        $registeredTool = new RegisteredTool($this->sampleTool, $failingCallable);

        $this->expectException(ToolError::class);
        $this->expectExceptionMessage('Error executing tool add: Callable failed');

        $registeredTool->execute(['a' => 5, 'b' => 3]);
    }

    public function testGetInputSchema(): void
    {
        $registeredTool = new RegisteredTool($this->sampleTool, $this->sampleCallable);
        $schema = $registeredTool->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('a', $schema['properties']);
        $this->assertArrayHasKey('b', $schema['properties']);
    }

    public function testGetAnnotations(): void
    {
        $annotations = new ToolAnnotations(
            'Test Tool',
            null,
            true
        );

        $schema = ['type' => 'object', 'properties' => []];
        $tool = new Tool('test', $schema, 'Test tool', $annotations);
        $registeredTool = new RegisteredTool($tool, $this->sampleCallable);

        $this->assertSame($annotations, $registeredTool->getAnnotations());
        $this->assertEquals('Test Tool', $registeredTool->getAnnotations()->getTitle());
    }

    public function testGetAnnotationsNull(): void
    {
        $registeredTool = new RegisteredTool($this->sampleTool, $this->sampleCallable);

        $this->assertNull($registeredTool->getAnnotations());
    }

    public function testWithDifferentCallableTypes(): void
    {
        // Test with different callable types
        $callables = [
            // Closure
            function (array $args): string {
                return 'closure result';
            },
            // Anonymous function
            function (array $args) {
                return 'regular function result';
            },
        ];

        foreach ($callables as $callable) {
            $schema = ['type' => 'object', 'properties' => []];
            $tool = new Tool('test', $schema, 'Test tool');
            $registeredTool = new RegisteredTool($tool, $callable);

            $this->assertInstanceOf(RegisteredTool::class, $registeredTool);
        }
    }
}
