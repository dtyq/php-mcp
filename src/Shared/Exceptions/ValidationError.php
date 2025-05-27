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
        $contextStr = $context ? " for {$context}" : '';
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
     * Create a ValidationError for invalid field value.
     *
     * @param string $fieldName The name of the field
     * @param string $reason The reason why the value is invalid
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidFieldValue(string $fieldName, string $reason, $data = null): ValidationError
    {
        return new self("Invalid value for field '{$fieldName}': {$reason}", $data);
    }

    /**
     * Create a ValidationError for empty field.
     *
     * @param string $fieldName The name of the field
     * @param mixed $data Additional error data (optional)
     */
    public static function emptyField(string $fieldName, $data = null): ValidationError
    {
        return new self("Field '{$fieldName}' cannot be empty", $data);
    }

    /**
     * Create a ValidationError for invalid content type.
     *
     * @param string $expectedType The expected content type
     * @param string $actualType The actual content type received
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidContentType(string $expectedType, string $actualType, $data = null): ValidationError
    {
        return new self("Invalid content type: expected {$expectedType}, got {$actualType}", $data);
    }

    /**
     * Create a ValidationError for unsupported content type.
     *
     * @param string $contentType The unsupported content type
     * @param string $context The context where the content type is not supported
     * @param mixed $data Additional error data (optional)
     */
    public static function unsupportedContentType(string $contentType, string $context = '', $data = null): ValidationError
    {
        $contextStr = $context ? " for {$context}" : '';
        return new self("Unsupported content type '{$contentType}'{$contextStr}", $data);
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

    /**
     * Create a ValidationError for invalid base64 encoding.
     *
     * @param string $fieldName The name of the field containing invalid base64
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidBase64(string $fieldName, $data = null): ValidationError
    {
        return new self("Field '{$fieldName}' must be valid base64 encoded", $data);
    }

    /**
     * Create a ValidationError for file operation errors.
     *
     * @param string $operation The file operation that failed
     * @param string $filePath The file path
     * @param string $reason The reason for failure
     * @param mixed $data Additional error data (optional)
     */
    public static function fileOperationError(string $operation, string $filePath, string $reason, $data = null): ValidationError
    {
        return new self("Failed to {$operation} file '{$filePath}': {$reason}", $data);
    }

    /**
     * Create a ValidationError for argument validation.
     *
     * @param string $argumentName The name of the argument
     * @param string $expectedType The expected type
     * @param string $actualType The actual type received
     * @param mixed $data Additional error data (optional)
     */
    public static function invalidArgumentType(
        string $argumentName,
        string $expectedType,
        string $actualType,
        $data = null
    ): ValidationError {
        return new self(
            "Argument '{$argumentName}' must be a {$expectedType}, {$actualType} given",
            $data
        );
    }

    /**
     * Create a ValidationError for missing required argument.
     *
     * @param string $argumentName The name of the missing argument
     * @param mixed $data Additional error data (optional)
     */
    public static function missingRequiredArgument(string $argumentName, $data = null): ValidationError
    {
        return new self("Required argument '{$argumentName}' is missing", $data);
    }
}
