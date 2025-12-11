<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\FastMcp\Prompts;

use Closure;
use Dtyq\PhpMcp\Server\FastMcp\Prompts\RegisteredPrompt;
use Dtyq\PhpMcp\Shared\Exceptions\PromptError;
use Dtyq\PhpMcp\Shared\Exceptions\SystemException;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\StaticMethodCall;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for RegisteredPrompt class.
 * @internal
 */
class RegisteredPromptTest extends TestCase
{
    private Prompt $samplePrompt;

    private Closure $sampleCallable;

    protected function setUp(): void
    {
        $arguments = [
            new PromptArgument('name', 'The name to greet', true),
            new PromptArgument('style', 'Greeting style', false),
        ];

        $this->samplePrompt = new Prompt('greeting', 'Generate a greeting message', $arguments);

        $this->sampleCallable = function (array $args): GetPromptResult {
            $name = $args['name'] ?? 'World';
            $style = $args['style'] ?? 'casual';

            $greeting = $style === 'formal'
                ? "Good day, {$name}."
                : "Hello, {$name}!";

            $message = new PromptMessage(
                MessageConstants::ROLE_USER,
                new TextContent($greeting)
            );

            return new GetPromptResult('Greeting generated', [$message]);
        };
    }

    public function testConstructor(): void
    {
        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $this->sampleCallable);

        $this->assertSame($this->samplePrompt, $registeredPrompt->getPrompt());
        $this->assertEquals('greeting', $registeredPrompt->getName());
        $this->assertEquals('Generate a greeting message', $registeredPrompt->getDescription());
    }

    public function testConstructorWithNeitherCallableNorStaticMethod(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('callable/staticMethod');

        new RegisteredPrompt($this->samplePrompt, null, null);
    }

    public function testWithCallableFactoryMethod(): void
    {
        $registeredPrompt = RegisteredPrompt::withCallable($this->samplePrompt, $this->sampleCallable);

        $this->assertTrue($registeredPrompt->hasCallable());
        $this->assertFalse($registeredPrompt->hasStaticMethod());
    }

    public function testWithStaticMethodFactoryMethod(): void
    {
        $staticMethod = new StaticMethodCall(
            RegisteredPromptTestHelper::class,
            'generateGreeting'
        );

        $registeredPrompt = RegisteredPrompt::withStaticMethod($this->samplePrompt, $staticMethod);

        $this->assertFalse($registeredPrompt->hasCallable());
        $this->assertTrue($registeredPrompt->hasStaticMethod());
        $this->assertSame($staticMethod, $registeredPrompt->getStaticMethod());
    }

    public function testExecuteSuccess(): void
    {
        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $this->sampleCallable);
        $result = $registeredPrompt->execute(['name' => 'Alice', 'style' => 'formal']);

        $this->assertInstanceOf(GetPromptResult::class, $result);
        $this->assertEquals('Greeting generated', $result->getDescription());
        $this->assertCount(1, $result->getMessages());

        $message = $result->getMessages()[0];
        $this->assertEquals(MessageConstants::ROLE_USER, $message->getRole());
        $this->assertEquals('Good day, Alice.', $message->getTextContent());
    }

    public function testExecuteWithStaticMethod(): void
    {
        $staticMethod = new StaticMethodCall(
            RegisteredPromptTestHelper::class,
            'generateGreeting'
        );

        $registeredPrompt = RegisteredPrompt::withStaticMethod($this->samplePrompt, $staticMethod);
        $result = $registeredPrompt->execute(['name' => 'Bob']);

        $this->assertInstanceOf(GetPromptResult::class, $result);
        $this->assertEquals('Static greeting', $result->getDescription());

        $message = $result->getMessages()[0];
        $this->assertEquals('Static hello to Bob!', $message->getTextContent());
    }

    public function testStaticMethodTakesPrecedence(): void
    {
        $staticMethod = new StaticMethodCall(
            RegisteredPromptTestHelper::class,
            'generateGreeting'
        );

        $callableThatReturnsDifferent = function (array $args): GetPromptResult {
            $message = new PromptMessage(
                MessageConstants::ROLE_USER,
                new TextContent('Callable greeting')
            );
            return new GetPromptResult('Callable generated', [$message]);
        };

        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $callableThatReturnsDifferent, $staticMethod);
        $result = $registeredPrompt->execute(['name' => 'Test']);

        // Should use static method instead of callable
        $this->assertEquals('Static greeting', $result->getDescription());
    }

    public function testExecuteWithDefaultArgument(): void
    {
        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $this->sampleCallable);
        $result = $registeredPrompt->execute(['name' => 'Bob']);

        $this->assertInstanceOf(GetPromptResult::class, $result);
        $message = $result->getMessages()[0];
        $this->assertEquals('Hello, Bob!', $message->getTextContent());
    }

    public function testExecuteWithValidationFailure(): void
    {
        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $this->sampleCallable);

        $this->expectException(PromptError::class);
        $this->expectExceptionMessage('Error executing prompt greeting:');

        // Missing required parameter 'name'
        $registeredPrompt->execute(['style' => 'formal']);
    }

    public function testExecuteWithCallableException(): void
    {
        $failingCallable = function (array $args): void {
            throw new SystemException('Callable failed');
        };

        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $failingCallable);

        $this->expectException(PromptError::class);
        $this->expectExceptionMessage('Error executing prompt greeting: Callable failed');

        $registeredPrompt->execute(['name' => 'Alice']);
    }

    public function testExecuteWithStaticMethodException(): void
    {
        $staticMethod = new StaticMethodCall(
            RegisteredPromptTestHelper::class,
            'throwException'
        );

        $registeredPrompt = RegisteredPrompt::withStaticMethod($this->samplePrompt, $staticMethod);

        $this->expectException(PromptError::class);
        $this->expectExceptionMessage('Error executing prompt greeting: Static method failed');

        $registeredPrompt->execute(['name' => 'Alice']);
    }

    public function testExecuteWithInvalidReturnType(): void
    {
        $invalidCallable = function (array $args): stdClass {
            return new stdClass(); // Return an object that cannot be converted
        };

        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $invalidCallable);

        $this->expectException(PromptError::class);
        $this->expectExceptionMessage('Prompt callable must return GetPromptResult instance');

        $registeredPrompt->execute(['name' => 'Alice']);
    }

    public function testGetArguments(): void
    {
        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $this->sampleCallable);
        $arguments = $registeredPrompt->getArguments();

        $this->assertCount(2, $arguments);
        $this->assertEquals('name', $arguments[0]->getName());
        $this->assertEquals('style', $arguments[1]->getName());
    }

    public function testHasArguments(): void
    {
        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $this->sampleCallable);
        $this->assertTrue($registeredPrompt->hasArguments());

        // Test with prompt without arguments
        $simplePrompt = new Prompt('simple', 'Simple prompt');
        $simpleRegistered = new RegisteredPrompt($simplePrompt, $this->sampleCallable);
        $this->assertFalse($simpleRegistered->hasArguments());
    }

    public function testGetRequiredArguments(): void
    {
        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $this->sampleCallable);
        $requiredArgs = $registeredPrompt->getRequiredArguments();

        $this->assertCount(1, $requiredArgs);
        $this->assertEquals('name', $requiredArgs[0]->getName());
        $this->assertTrue($requiredArgs[0]->isRequired());
    }

    public function testGetOptionalArguments(): void
    {
        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $this->sampleCallable);
        $optionalArgs = $registeredPrompt->getOptionalArguments();

        $this->assertCount(1, $optionalArgs);
        $firstOptional = reset($optionalArgs); // Get first element without relying on index
        $this->assertEquals('style', $firstOptional->getName());
        $this->assertFalse($firstOptional->isRequired());
    }

    public function testWithDifferentCallableTypes(): void
    {
        // Test with different callable types
        $callables = [
            // Closure
            function (array $args): GetPromptResult {
                return new GetPromptResult('Test', []);
            },
            // Arrow function
            fn (array $args) => new GetPromptResult('Test', []),
        ];

        foreach ($callables as $callable) {
            $prompt = new Prompt('test', 'Test prompt');
            $registeredPrompt = new RegisteredPrompt($prompt, $callable);

            $this->assertInstanceOf(RegisteredPrompt::class, $registeredPrompt);
        }
    }

    public function testComplexPromptWithMultipleMessages(): void
    {
        $complexCallable = function (array $args): GetPromptResult {
            $messages = [
                new PromptMessage(
                    MessageConstants::ROLE_USER,
                    new TextContent('System: Please review the following code.')
                ),
                new PromptMessage(
                    MessageConstants::ROLE_ASSISTANT,
                    new TextContent('I\'ll review the code for you.')
                ),
                new PromptMessage(
                    MessageConstants::ROLE_USER,
                    new TextContent($args['code'] ?? 'No code provided')
                ),
            ];

            return new GetPromptResult('Code review conversation', $messages);
        };

        $prompt = new Prompt('code_review', 'Code review prompt', [
            new PromptArgument('code', 'Code to review', true),
        ]);

        $registeredPrompt = new RegisteredPrompt($prompt, $complexCallable);
        $result = $registeredPrompt->execute(['code' => 'function test() { return true; }']);

        $this->assertCount(3, $result->getMessages());
        $this->assertEquals('Code review conversation', $result->getDescription());
    }

    public function testHasStaticMethodReturnsFalseForCallableOnly(): void
    {
        $registeredPrompt = new RegisteredPrompt($this->samplePrompt, $this->sampleCallable);

        $this->assertFalse($registeredPrompt->hasStaticMethod());
        $this->assertTrue($registeredPrompt->hasCallable());
        $this->assertNull($registeredPrompt->getStaticMethod());
    }
}

/**
 * Helper class for testing RegisteredPrompt with static methods.
 * @internal
 */
class RegisteredPromptTestHelper
{
    /**
     * Static method that generates a greeting.
     *
     * @param array<string, mixed> $args
     */
    public static function generateGreeting(array $args): GetPromptResult
    {
        $name = $args['name'] ?? 'World';

        $message = new PromptMessage(
            MessageConstants::ROLE_USER,
            new TextContent("Static hello to {$name}!")
        );

        return new GetPromptResult('Static greeting', [$message]);
    }

    /**
     * Static method that throws an exception.
     *
     * @param array<string, mixed> $args
     */
    public static function throwException(array $args): void
    {
        throw new SystemException('Static method failed');
    }
}
