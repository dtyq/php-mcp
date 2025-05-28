<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

use Exception;

/**
 * Exception raised when an error occurs during resource access.
 *
 * This exception is thrown when a resource fails to be accessed properly,
 * including validation errors, runtime errors, or other resource-related issues.
 */
class ResourceError extends McpError
{
    /** @var string The URI of the resource that caused the error */
    private string $resourceUri;

    /**
     * Initialize ResourceError.
     *
     * @param string $message The error message
     * @param string $resourceUri The URI of the resource that caused the error
     * @param int $code The error code
     * @param ?Exception $previous The original exception that caused this error
     */
    public function __construct(string $message, string $resourceUri = '', int $code = -1, ?Exception $previous = null)
    {
        $this->resourceUri = $resourceUri;

        // Create ErrorData for parent McpError
        $errorData = new ErrorData($code, $message, ['resourceUri' => $resourceUri]);

        parent::__construct($errorData, $previous);
    }

    /**
     * Get the URI of the resource that caused the error.
     */
    public function getResourceUri(): string
    {
        return $this->resourceUri;
    }

    /**
     * Create a ResourceError for an unknown resource.
     */
    public static function unknownResource(string $resourceUri): self
    {
        return new self("Unknown resource: {$resourceUri}", $resourceUri, ErrorCodes::RESOURCE_NOT_FOUND);
    }

    /**
     * Create a ResourceError for resource access failure.
     */
    public static function accessFailed(string $resourceUri, Exception $originalException): self
    {
        return new self(
            "Error accessing resource {$resourceUri}: " . $originalException->getMessage(),
            $resourceUri,
            ErrorCodes::INTERNAL_ERROR,
            $originalException
        );
    }

    /**
     * Create a ResourceError for resource validation failure.
     */
    public static function validationFailed(string $resourceUri, string $reason): self
    {
        return new self(
            "Resource validation failed for {$resourceUri}: {$reason}",
            $resourceUri,
            ErrorCodes::INVALID_PARAMS
        );
    }

    /**
     * Create a ResourceError for resource not found.
     */
    public static function notFound(string $resourceUri): self
    {
        return new self(
            "Resource not found: {$resourceUri}",
            $resourceUri,
            ErrorCodes::RESOURCE_NOT_FOUND
        );
    }
}
