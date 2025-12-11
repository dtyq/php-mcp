<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Tools;

use Closure;
use Dtyq\PhpMcp\Shared\Exceptions\ToolError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\StaticMethodCall;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Dtyq\PhpMcp\Types\Tools\ToolAnnotations;
use Exception;
use Opis\Closure\SerializableClosure;

/**
 * Registered tool definition and execution class.
 *
 * Stores tool metadata and handles tool execution.
 *
 * Supports two execution modes:
 * 1. Closure/Callable mode: Traditional closure-based execution
 * 2. Static method mode: Serialization-safe static method call
 *
 * For HTTP transport with session persistence, prefer static method mode
 * to avoid closure serialization issues with complex objects.
 */
class RegisteredTool
{
    /** @var Tool Tool metadata */
    private Tool $tool;

    /** @var null|Closure|SerializableClosure The function to execute */
    private $callable;

    /** @var null|StaticMethodCall Static method call for serialization-safe execution */
    private ?StaticMethodCall $staticMethod;

    /**
     * Create a registered tool with either a callable or static method.
     *
     * Either $callable or $staticMethod must be provided, but not both empty.
     * If both are provided, staticMethod takes precedence during execution.
     *
     * @param Tool $tool Tool metadata
     * @param null|Closure|SerializableClosure $callable The function to execute
     * @param null|StaticMethodCall $staticMethod Static method call for serialization-safe execution
     * @throws ValidationError If neither callable nor staticMethod is provided
     */
    public function __construct(Tool $tool, $callable = null, ?StaticMethodCall $staticMethod = null)
    {
        if ($callable === null && $staticMethod === null) {
            throw ValidationError::invalidFieldValue(
                'callable/staticMethod',
                'null',
                'Either callable or staticMethod must be provided'
            );
        }

        $this->tool = $tool;
        $this->callable = $callable;
        $this->staticMethod = $staticMethod;
    }

    /**
     * Create a registered tool with a callable.
     *
     * @param Tool $tool Tool metadata
     * @param Closure|SerializableClosure $callable The function to execute
     */
    public static function withCallable(Tool $tool, $callable): self
    {
        return new self($tool, $callable, null);
    }

    /**
     * Create a registered tool with a static method.
     *
     * @param Tool $tool Tool metadata
     * @param StaticMethodCall $staticMethod Static method call
     */
    public static function withStaticMethod(Tool $tool, StaticMethodCall $staticMethod): self
    {
        return new self($tool, null, $staticMethod);
    }

    /**
     * Execute the tool with given arguments.
     *
     * If staticMethod is set, it takes precedence over callable.
     *
     * @param array<string, mixed> $arguments
     * @return mixed
     */
    public function execute(array $arguments)
    {
        try {
            // Validate arguments
            if (! $this->tool->validateArguments($arguments)) {
                throw ToolError::validationFailed(
                    $this->tool->getName(),
                    'Invalid arguments provided'
                );
            }

            // Prefer static method if available (serialization-safe)
            if ($this->staticMethod !== null) {
                return $this->staticMethod->invoke($arguments);
            }

            // Fallback to callable
            if ($this->callable !== null) {
                return call_user_func($this->callable, $arguments);
            }

            // This should never happen due to constructor validation
            throw ToolError::executionFailed(
                $this->tool->getName(),
                new Exception('No callable or static method configured')
            );
        } catch (Exception $e) {
            if ($e instanceof ToolError) {
                throw $e;
            }
            throw ToolError::executionFailed($this->tool->getName(), $e);
        }
    }

    /**
     * Get tool metadata.
     */
    public function getTool(): Tool
    {
        return $this->tool;
    }

    /**
     * Get tool name.
     */
    public function getName(): string
    {
        return $this->tool->getName();
    }

    /**
     * Get tool description.
     */
    public function getDescription(): ?string
    {
        return $this->tool->getDescription();
    }

    /**
     * Get input schema.
     *
     * @return array<string, mixed>
     */
    public function getInputSchema(): array
    {
        return $this->tool->getInputSchema();
    }

    /**
     * Get annotations.
     */
    public function getAnnotations(): ?ToolAnnotations
    {
        return $this->tool->getAnnotations();
    }

    /**
     * Check if this tool uses static method mode.
     */
    public function hasStaticMethod(): bool
    {
        return $this->staticMethod !== null;
    }

    /**
     * Check if this tool uses callable mode.
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
