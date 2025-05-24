<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

/**
 * Exception for validation errors.
 *
 * This exception is thrown when there are input validation failures,
 * data format errors, or schema validation issues.
 */
class ValidationError extends McpError
{
    /**
     * Create a ValidationError with a specific error message.
     *
     * @param string $message The error message
     * @param mixed $data Additional error data (optional)
     */
    public function __construct(string $message, $data = null)
    {
        $error = new ErrorData(ErrorCodes::VALIDATION_ERROR, $message, $data);
        parent::__construct($error);
    }

    /**
     * Create a ValidationError for required field missing.
     *
     * @param string $fieldName The name of the missing field
     * @param string $context The context where the field is required
     * @param mixed $data Additional error data (optional)
     */
    public static function requiredFieldMissing(string $fieldName, string $context = '', $data = null): ValidationError
    {
        $contextStr = $context ? " in {$context}" : '';
        return new self("Required field '{$fieldName}' is missing{$contextStr}", $data);
    }

    /**
     * Create a ValidationError for invalid field type.
     *
     * @param string $fieldName The name of the field
     * @param string $expectedType The expected type
     * @param string $actualType The actual type received
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidFieldType(
        string $fieldName,
        string $expectedType,
        string $actualType,
        $data = null
    ): ValidationError {
        return new self(
            "Invalid type for field '{$fieldName}': expected {$expectedType}, got {$actualType}",
            $data
        );
    }

    /**
     * Create a ValidationError for invalid JSON format.
     *
     * @param string $reason The reason for invalid JSON
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidJsonFormat(string $reason, $data = null): ValidationError
    {
        $error = new ErrorData(ErrorCodes::PARSE_ERROR, "Invalid JSON format: {$reason}", $data);
        $exception = new McpError($error);
        return new self($exception->getMessage(), $data);
    }
}
