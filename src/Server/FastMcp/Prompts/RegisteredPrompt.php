<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Prompts;

use Closure;
use Dtyq\PhpMcp\Shared\Exceptions\PromptError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\StaticMethodCall;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Exception;
use Opis\Closure\SerializableClosure;

/**
 * Registered prompt definition and execution class.
 *
 * Stores prompt metadata and handles prompt execution.
 *
 * Supports two execution modes:
 * 1. Closure/Callable mode: Traditional closure-based execution
 * 2. Static method mode: Serialization-safe static method call
 *
 * For HTTP transport with session persistence, prefer static method mode
 * to avoid closure serialization issues with complex objects.
 */
class RegisteredPrompt
{
    /** @var Prompt Prompt metadata */
    private Prompt $prompt;

    /** @var null|Closure|SerializableClosure The function to execute */
    private $callable;

    /** @var null|StaticMethodCall Static method call for serialization-safe execution */
    private ?StaticMethodCall $staticMethod;

    /**
     * Create a registered prompt with either a callable or static method.
     *
     * Either $callable or $staticMethod must be provided, but not both empty.
     * If both are provided, staticMethod takes precedence during execution.
     *
     * @param Prompt $prompt Prompt metadata
     * @param null|Closure|SerializableClosure $callable The function to execute
     * @param null|StaticMethodCall $staticMethod Static method call for serialization-safe execution
     * @throws ValidationError If neither callable nor staticMethod is provided
     */
    public function __construct(Prompt $prompt, $callable = null, ?StaticMethodCall $staticMethod = null)
    {
        if ($callable === null && $staticMethod === null) {
            throw ValidationError::invalidFieldValue(
                'callable/staticMethod',
                'null',
                'Either callable or staticMethod must be provided'
            );
        }

        $this->prompt = $prompt;
        $this->callable = $callable;
        $this->staticMethod = $staticMethod;
    }

    /**
     * Create a registered prompt with a callable.
     *
     * @param Prompt $prompt Prompt metadata
     * @param Closure|SerializableClosure $callable The function to execute
     */
    public static function withCallable(Prompt $prompt, $callable): self
    {
        return new self($prompt, $callable, null);
    }

    /**
     * Create a registered prompt with a static method.
     *
     * @param Prompt $prompt Prompt metadata
     * @param StaticMethodCall $staticMethod Static method call
     */
    public static function withStaticMethod(Prompt $prompt, StaticMethodCall $staticMethod): self
    {
        return new self($prompt, null, $staticMethod);
    }

    /**
     * Execute the prompt with given arguments.
     *
     * If staticMethod is set, it takes precedence over callable.
     *
     * @param array<string, mixed> $arguments
     */
    public function execute(array $arguments): GetPromptResult
    {
        try {
            // Validate arguments
            $this->prompt->validateArguments($arguments);

            // Prefer static method if available (serialization-safe)
            if ($this->staticMethod !== null) {
                $result = $this->staticMethod->invoke($arguments);
            } elseif ($this->callable !== null) {
                // Execute the callable
                $result = call_user_func($this->callable, $arguments);
            } else {
                // This should never happen due to constructor validation
                throw PromptError::executionFailed(
                    $this->prompt->getName(),
                    new Exception('No callable or static method configured')
                );
            }

            if (is_array($result)) {
                $result = json_encode($result, JSON_UNESCAPED_UNICODE);
            }
            if (is_string($result)) {
                $message = new PromptMessage(MessageConstants::ROLE_USER, new TextContent($result));
                $result = new GetPromptResult(null, [$message]);
            }

            // Ensure result is GetPromptResult
            if (! $result instanceof GetPromptResult) {
                throw PromptError::executionFailed(
                    $this->prompt->getName(),
                    new Exception('Prompt callable must return GetPromptResult instance')
                );
            }

            return $result;
        } catch (Exception $e) {
            if ($e instanceof PromptError) {
                throw $e;
            }
            throw PromptError::executionFailed($this->prompt->getName(), $e);
        }
    }

    /**
     * Get prompt metadata.
     */
    public function getPrompt(): Prompt
    {
        return $this->prompt;
    }

    /**
     * Get prompt name.
     */
    public function getName(): string
    {
        return $this->prompt->getName();
    }

    /**
     * Get prompt description.
     */
    public function getDescription(): ?string
    {
        return $this->prompt->getDescription();
    }

    /**
     * Get prompt arguments.
     *
     * @return array<PromptArgument>
     */
    public function getArguments(): array
    {
        return $this->prompt->getArguments();
    }

    /**
     * Check if prompt has arguments.
     */
    public function hasArguments(): bool
    {
        return $this->prompt->hasArguments();
    }

    /**
     * Get required arguments.
     *
     * @return array<PromptArgument>
     */
    public function getRequiredArguments(): array
    {
        return $this->prompt->getRequiredArguments();
    }

    /**
     * Get optional arguments.
     *
     * @return array<PromptArgument>
     */
    public function getOptionalArguments(): array
    {
        return $this->prompt->getOptionalArguments();
    }

    /**
     * Check if this prompt uses static method mode.
     */
    public function hasStaticMethod(): bool
    {
        return $this->staticMethod !== null;
    }

    /**
     * Check if this prompt uses callable mode.
     */
    public function hasCallable(): bool
    {
        return $this->callable !== null;
    }

    /**
     * Get the static method call (if set).
     */
    public function getStaticMethod(): ?StaticMethodCall
    {
        return $this->staticMethod;
    }
}
