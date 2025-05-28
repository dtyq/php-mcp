<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

use Exception;

/**
 * Exception raised when an error occurs during prompt execution.
 *
 * This exception is thrown when a prompt fails to execute properly,
 * including validation errors, runtime errors, or other prompt-related issues.
 */
class PromptError extends McpError
{
    /** @var string The name of the prompt that caused the error */
    private string $promptName;

    /**
     * Initialize PromptError.
     *
     * @param string $message The error message
     * @param string $promptName The name of the prompt that caused the error
     * @param int $code The error code
     * @param ?Exception $previous The original exception that caused this error
     */
    public function __construct(string $message, string $promptName = '', int $code = -1, ?Exception $previous = null)
    {
        $this->promptName = $promptName;

        // Create ErrorData for parent McpError
        $errorData = new ErrorData($code, $message, ['promptName' => $promptName]);

        parent::__construct($errorData, $previous);
    }

    /**
     * Get the name of the prompt that caused the error.
     */
    public function getPromptName(): string
    {
        return $this->promptName;
    }

    /**
     * Create a PromptError for an unknown prompt.
     */
    public static function unknownPrompt(string $promptName): self
    {
        return new self("Unknown prompt: {$promptName}", $promptName, ErrorCodes::PROMPT_NOT_FOUND);
    }

    /**
     * Create a PromptError for prompt execution failure.
     */
    public static function executionFailed(string $promptName, Exception $originalException): self
    {
        return new self(
            "Error executing prompt {$promptName}: " . $originalException->getMessage(),
            $promptName,
            ErrorCodes::INTERNAL_ERROR,
            $originalException
        );
    }

    /**
     * Create a PromptError for prompt validation failure.
     */
    public static function validationFailed(string $promptName, string $reason): self
    {
        return new self(
            "Prompt validation failed for {$promptName}: {$reason}",
            $promptName,
            ErrorCodes::INVALID_PARAMS
        );
    }
}
