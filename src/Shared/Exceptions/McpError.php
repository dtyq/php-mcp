<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Exceptions;

use Exception;

/**
 * Exception type raised when an error arrives over an MCP connection.
 *
 * This class corresponds to the McpError from Python SDK and provides
 * structured error information compatible with JSON-RPC 2.0 error format.
 */
class McpError extends Exception
{
    private ErrorData $error;

    /** @var ?Exception The original exception that caused this error */
    private ?Exception $originalException;

    /**
     * Initialize McpError with ErrorData.
     *
     * @param ErrorData $error The structured error data
     * @param ?Exception $previous The original exception that caused this error
     */
    public function __construct(ErrorData $error, ?Exception $previous = null)
    {
        parent::__construct($error->getMessage(), 0, $previous);
        $this->error = $error;
        $this->originalException = $previous;
    }

    /**
     * Get the structured error data.
     *
     * @return ErrorData The error data containing code, message, and optional data
     */
    public function getError(): ErrorData
    {
        return $this->error;
    }

    /**
     * Get the error code from the structured error data.
     *
     * @return int The error code
     */
    public function getErrorCode(): int
    {
        return $this->error->getCode();
    }

    /**
     * Get additional error data if available.
     *
     * @return mixed Additional error data or null
     */
    public function getErrorData()
    {
        return $this->error->getData();
    }

    /**
     * Get the original exception that caused this error.
     */
    public function getOriginalException(): ?Exception
    {
        return $this->originalException;
    }

    /**
     * Convert the error to an array format suitable for JSON-RPC response.
     *
     * @return array{code: int, message: string, data?: mixed}
     */
    public function toArray(): array
    {
        return $this->error->toArray();
    }
}
