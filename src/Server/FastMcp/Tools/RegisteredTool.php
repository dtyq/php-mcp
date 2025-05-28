<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Tools;

use Closure;
use Dtyq\PhpMcp\Shared\Exceptions\ToolError;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Dtyq\PhpMcp\Types\Tools\ToolAnnotations;
use Exception;

/**
 * Registered tool definition and execution class.
 *
 * Stores tool metadata and handles tool execution.
 */
class RegisteredTool
{
    /** @var Tool Tool metadata */
    private Tool $tool;

    /** @var Closure The function to execute */
    private Closure $callable;

    public function __construct(Tool $tool, Closure $callable)
    {
        $this->tool = $tool;
        $this->callable = $callable;
    }

    /**
     * Execute the tool with given arguments.
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

            // Execute the callable
            return call_user_func($this->callable, $arguments);
        } catch (Exception $e) {
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
}
