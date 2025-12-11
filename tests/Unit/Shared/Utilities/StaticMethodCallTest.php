<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Shared\Utilities;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\StaticMethodCall;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for StaticMethodCall class.
 * @internal
 */
class StaticMethodCallTest extends TestCase
{
    public function testConstructorWithValidStaticMethod(): void
    {
        $call = new StaticMethodCall(
            StaticMethodCallTestHelper::class,
            'staticAdd'
        );

        $this->assertEquals(StaticMethodCallTestHelper::class, $call->getClassName());
        $this->assertEquals('staticAdd', $call->getMethodName());
        $this->assertEmpty($call->getBoundParams());
        $this->assertFalse($call->hasBoundParams());
    }

    public function testConstructorWithBoundParams(): void
    {
        $call = new StaticMethodCall(
            StaticMethodCallTestHelper::class,
            'staticWithConfig',
            ['prefix' => 'Hello']
        );

        $this->assertTrue($call->hasBoundParams());
        $this->assertEquals(['prefix' => 'Hello'], $call->getBoundParams());
    }

    public function testInvokeStaticMethod(): void
    {
        $call = new StaticMethodCall(
            StaticMethodCallTestHelper::class,
            'staticAdd'
        );

        $result = $call->invoke(['a' => 5, 'b' => 3]);
        $this->assertEquals(8, $result);
    }

    public function testInvokeWithBoundParams(): void
    {
        $call = new StaticMethodCall(
            StaticMethodCallTestHelper::class,
            'staticWithConfig',
            ['prefix' => 'Hello']
        );

        $result = $call->invoke(['name' => 'World']);
        $this->assertEquals('Hello, World!', $result);
    }

    public function testInvokeWithBoundParamsOverride(): void
    {
        $call = new StaticMethodCall(
            StaticMethodCallTestHelper::class,
            'staticWithConfig',
            ['prefix' => 'Hello', 'name' => 'Default']
        );

        // Runtime arguments should override bound params
        $result = $call->invoke(['name' => 'Override']);
        $this->assertEquals('Hello, Override!', $result);
    }

    public function testConstructorWithNonExistentClass(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('className');

        new StaticMethodCall('NonExistentClass', 'someMethod');
    }

    public function testConstructorWithNonExistentMethod(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('methodName');

        new StaticMethodCall(StaticMethodCallTestHelper::class, 'nonExistentMethod');
    }

    public function testConstructorWithNonStaticMethod(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('methodName');

        new StaticMethodCall(StaticMethodCallTestHelper::class, 'instanceMethod');
    }

    public function testConstructorWithPrivateMethod(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('methodName');

        new StaticMethodCall(StaticMethodCallTestHelper::class, 'privateStaticMethod');
    }

    public function testConstructorWithEmptyClassName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('className');

        new StaticMethodCall('', 'someMethod');
    }

    public function testConstructorWithEmptyMethodName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('methodName');

        new StaticMethodCall(StaticMethodCallTestHelper::class, '');
    }

    public function testConstructorWithObjectInBoundParams(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('boundParams');

        new StaticMethodCall(
            StaticMethodCallTestHelper::class,
            'staticAdd',
            ['obj' => new stdClass()]
        );
    }

    public function testConstructorWithNestedArraysInBoundParams(): void
    {
        // Nested arrays of scalars should be allowed
        $call = new StaticMethodCall(
            StaticMethodCallTestHelper::class,
            'staticWithConfig',
            ['config' => ['nested' => ['value' => 'test']]]
        );

        $this->assertTrue($call->hasBoundParams());
    }

    public function testToCallable(): void
    {
        $call = new StaticMethodCall(
            StaticMethodCallTestHelper::class,
            'staticAdd'
        );

        $callable = $call->toCallable();

        $this->assertIsArray($callable);
        $this->assertEquals(StaticMethodCallTestHelper::class, $callable[0]);
        $this->assertEquals('staticAdd', $callable[1]);
    }

    public function testToArray(): void
    {
        $call = new StaticMethodCall(
            StaticMethodCallTestHelper::class,
            'staticAdd',
            ['test' => 'value']
        );

        $array = $call->toArray();

        $this->assertArrayHasKey('class', $array);
        $this->assertArrayHasKey('method', $array);
        $this->assertArrayHasKey('params', $array);
        $this->assertEquals(StaticMethodCallTestHelper::class, $array['class']);
        $this->assertEquals('staticAdd', $array['method']);
        $this->assertEquals(['test' => 'value'], $array['params']);
    }

    public function testFromArray(): void
    {
        $data = [
            'class' => StaticMethodCallTestHelper::class,
            'method' => 'staticAdd',
            'params' => ['test' => 'value'],
        ];

        $call = StaticMethodCall::fromArray($data);

        $this->assertEquals(StaticMethodCallTestHelper::class, $call->getClassName());
        $this->assertEquals('staticAdd', $call->getMethodName());
        $this->assertEquals(['test' => 'value'], $call->getBoundParams());
    }

    public function testFromArrayWithMissingClass(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('class');

        StaticMethodCall::fromArray(['method' => 'staticAdd']);
    }

    public function testFromArrayWithMissingMethod(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('method');

        StaticMethodCall::fromArray(['class' => StaticMethodCallTestHelper::class]);
    }

    public function testFromArrayWithoutParams(): void
    {
        $data = [
            'class' => StaticMethodCallTestHelper::class,
            'method' => 'staticAdd',
        ];

        $call = StaticMethodCall::fromArray($data);

        $this->assertEmpty($call->getBoundParams());
    }
}

/**
 * Helper class for testing StaticMethodCall.
 * @internal
 */
class StaticMethodCallTestHelper
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
     * Static method with config support.
     *
     * @param array<string, mixed> $args
     */
    public static function staticWithConfig(array $args): string
    {
        $prefix = $args['prefix'] ?? 'Hi';
        $name = $args['name'] ?? 'Anonymous';
        return "{$prefix}, {$name}!";
    }

    /**
     * Instance method (not static).
     */
    public function instanceMethod(): string
    {
        return 'instance';
    }

    /**
     * Private static method (used for testing validation).
     *
     * @phpstan-ignore method.unused
     */
    private static function privateStaticMethod(): string
    {
        return 'private';
    }
}
