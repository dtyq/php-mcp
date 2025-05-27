<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorData;
use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Throwable;

/**
 * Interface for server error handling.
 *
 * This interface defines the contract for handling errors that occur
 * during server operation, including request processing errors,
 * transport errors, and system-level errors.
 */
interface ErrorHandlerInterface
{
    /**
     * Handle an error that occurred during request processing.
     *
     * @param Throwable $error The error that occurred
     * @param null|JsonRpcRequest $request The request being processed (if available)
     * @return JsonRpcError The error response to send to the client
     */
    public function handleRequestError(Throwable $error, ?JsonRpcRequest $request = null): JsonRpcError;

    /**
     * Handle a transport-level error.
     *
     * @param Throwable $error The transport error
     * @param array<string, mixed> $context Additional context information
     */
    public function handleTransportError(Throwable $error, array $context = []): void;

    /**
     * Handle a system-level error.
     *
     * @param Throwable $error The system error
     * @param array<string, mixed> $context Additional context information
     */
    public function handleSystemError(Throwable $error, array $context = []): void;

    /**
     * Register an error recovery strategy.
     *
     * @param string $errorType The type of error to handle
     * @param callable $strategy The recovery strategy callback
     * @param int $priority Priority (higher numbers execute first)
     */
    public function registerRecoveryStrategy(string $errorType, callable $strategy, int $priority = 0): void;

    /**
     * Attempt to recover from an error.
     *
     * @param Throwable $error The error to recover from
     * @param array<string, mixed> $context Recovery context
     * @return bool True if recovery was successful, false otherwise
     */
    public function attemptRecovery(Throwable $error, array $context = []): bool;

    /**
     * Get error statistics.
     *
     * @return array<string, mixed> Error handling statistics
     */
    public function getErrorStats(): array;

    /**
     * Set the maximum number of recovery attempts.
     *
     * @param int $maxAttempts Maximum recovery attempts
     */
    public function setMaxRecoveryAttempts(int $maxAttempts): void;

    /**
     * Enable or disable error reporting.
     *
     * @param bool $enabled Whether error reporting is enabled
     */
    public function setErrorReporting(bool $enabled): void;

    /**
     * Convert a throwable to ErrorData.
     *
     * @param Throwable $error The error to convert
     * @return ErrorData The structured error data
     */
    public function convertToErrorData(Throwable $error): ErrorData;
}
