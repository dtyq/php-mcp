<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

use Exception;

/**
 * Exception raised when an error occurs during tool execution.
 *
 * This exception is thrown when a tool fails to execute properly,
 * including validation errors, runtime errors, or other tool-related issues.
 */
class ToolError extends McpError
{
    /** @var string The name of the tool that caused the error */
    private string $toolName;

    /**
     * Initialize ToolError.
     *
     * @param string $message The error message
     * @param string $toolName The name of the tool that caused the error
     * @param int $code The error code
     * @param ?Exception $previous The original exception that caused this error
     */
    public function __construct(string $message, string $toolName = '', int $code = -1, ?Exception $previous = null)
    {
        $this->toolName = $toolName;

        // Create ErrorData for parent McpError
        $errorData = new ErrorData($code, $message, ['toolName' => $toolName]);

        parent::__construct($errorData, $previous);
    }

    /**
     * Get the name of the tool that caused the error.
     */
    public function getToolName(): string
    {
        return $this->toolName;
    }

    /**
     * Create a ToolError for an unknown tool.
     */
    public static function unknownTool(string $toolName): self
    {
        return new self("Unknown tool: {$toolName}", $toolName, -32601); // Method not found
    }

    /**
     * Create a ToolError for tool execution failure.
     */
    public static function executionFailed(string $toolName, Exception $originalException): self
    {
        return new self(
            "Error executing tool {$toolName}: " . $originalException->getMessage(),
            $toolName,
            -32603, // Internal error
            $originalException
        );
    }

    /**
     * Create a ToolError for tool validation failure.
     */
    public static function validationFailed(string $toolName, string $reason): self
    {
        return new self(
            "Tool validation failed for {$toolName}: {$reason}",
            $toolName,
            -32602 // Invalid params
        );
    }
}
