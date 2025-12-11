<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\FastMcp\Tools;

use Closure;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Shared\Exceptions\SystemException;
use Dtyq\PhpMcp\Shared\Exceptions\ToolError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\StaticMethodCall;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Dtyq\PhpMcp\Types\Tools\ToolAnnotations;
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

    public function testConstructorWithBothCallableAndStaticMethod(): void
    {
        $staticMethod = new StaticMethodCall(
            RegisteredToolTestHelper::class,
            'staticAdd'
        );

        $registeredTool = new RegisteredTool($this->sampleTool, $this->sampleCallable, $staticMethod);

        $this->assertTrue($registeredTool->hasCallable());
        $this->assertTrue($registeredTool->hasStaticMethod());
    }

    public function testConstructorWithNeitherCallableNorStaticMethod(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('callable/staticMethod');

        new RegisteredTool($this->sampleTool, null, null);
    }

    public function testWithCallableFactoryMethod(): void
    {
        $registeredTool = RegisteredTool::withCallable($this->sampleTool, $this->sampleCallable);

        $this->assertTrue($registeredTool->hasCallable());
        $this->assertFalse($registeredTool->hasStaticMethod());
    }

    public function testWithStaticMethodFactoryMethod(): void
    {
        $staticMethod = new StaticMethodCall(
            RegisteredToolTestHelper::class,
            'staticAdd'
        );

        $registeredTool = RegisteredTool::withStaticMethod($this->sampleTool, $staticMethod);

        $this->assertFalse($registeredTool->hasCallable());
        $this->assertTrue($registeredTool->hasStaticMethod());
        $this->assertSame($staticMethod, $registeredTool->getStaticMethod());
    }

    public function testExecuteSuccess(): void
    {
        $registeredTool = new RegisteredTool($this->sampleTool, $this->sampleCallable);
        $result = $registeredTool->execute(['a' => 5, 'b' => 3]);

        $this->assertEquals(8, $result);
    }

    public function testExecuteWithStaticMethod(): void
    {
        $staticMethod = new StaticMethodCall(
            RegisteredToolTestHelper::class,
            'staticAdd'
        );

        $registeredTool = RegisteredTool::withStaticMethod($this->sampleTool, $staticMethod);
        $result = $registeredTool->execute(['a' => 10, 'b' => 7]);

        $this->assertEquals(17, $result);
    }

    public function testExecuteStaticMethodTakesPrecedence(): void
    {
        // Static method returns different result from callable
        $staticMethod = new StaticMethodCall(
            RegisteredToolTestHelper::class,
            'staticMultiply'
        );

        $callableThatAdds = function (array $args): int {
            return $args['a'] + $args['b'];
        };

        $registeredTool = new RegisteredTool($this->sampleTool, $callableThatAdds, $staticMethod);

        // Should use static method (multiply) instead of callable (add)
        $result = $registeredTool->execute(['a' => 5, 'b' => 3]);

        $this->assertEquals(15, $result); // 5 * 3 = 15, not 5 + 3 = 8
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
            throw new SystemException('Callable failed');
        };

        $registeredTool = new RegisteredTool($this->sampleTool, $failingCallable);

        $this->expectException(ToolError::class);
        $this->expectExceptionMessage('Error executing tool add: Callable failed');

        $registeredTool->execute(['a' => 5, 'b' => 3]);
    }

    public function testExecuteWithStaticMethodException(): void
    {
        $staticMethod = new StaticMethodCall(
            RegisteredToolTestHelper::class,
            'staticThrowException'
        );

        $registeredTool = RegisteredTool::withStaticMethod($this->sampleTool, $staticMethod);

        $this->expectException(ToolError::class);
        $this->expectExceptionMessage('Error executing tool add: Static method failed');

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

    public function testHasStaticMethodReturnsFalseForCallableOnly(): void
    {
        $registeredTool = new RegisteredTool($this->sampleTool, $this->sampleCallable);

        $this->assertFalse($registeredTool->hasStaticMethod());
        $this->assertTrue($registeredTool->hasCallable());
        $this->assertNull($registeredTool->getStaticMethod());
    }

    public function testStaticMethodWithBoundParams(): void
    {
        $staticMethod = new StaticMethodCall(
            RegisteredToolTestHelper::class,
            'staticWithConfig',
            ['multiplier' => 2]
        );

        $schema = [
            'type' => 'object',
            'properties' => [
                'value' => ['type' => 'integer'],
            ],
            'required' => ['value'],
        ];
        $tool = new Tool('multiply', $schema, 'Multiply with config');

        $registeredTool = RegisteredTool::withStaticMethod($tool, $staticMethod);
        $result = $registeredTool->execute(['value' => 10]);

        $this->assertEquals(20, $result); // 10 * 2 = 20
    }
}

/**
 * Helper class for testing RegisteredTool with static methods.
 * @internal
 */
class RegisteredToolTestHelper
{
    /**
     * Static method that adds two numbers.
     *
     * @param array<string, mixed> $args
     */
    public static function staticAdd(array $args): int
    {
        return ($args['a'] ?? 0) + ($args['b'] ?? 0);
    }

    /**
     * Static method that multiplies two numbers.
     *
     * @param array<string, mixed> $args
     */
    public static function staticMultiply(array $args): int
    {
        return ($args['a'] ?? 0) * ($args['b'] ?? 1);
    }

    /**
     * Static method with config support.
     *
     * @param array<string, mixed> $args
     */
    public static function staticWithConfig(array $args): int
    {
        $value = $args['value'] ?? 0;
        $multiplier = $args['multiplier'] ?? 1;
        return $value * $multiplier;
    }

    /**
     * Static method that throws an exception.
     *
     * @param array<string, mixed> $args
     */
    public static function staticThrowException(array $args): void
    {
        throw new SystemException('Static method failed');
    }
}
