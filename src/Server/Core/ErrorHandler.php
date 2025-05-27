<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\ErrorData;
use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Error;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Default implementation of error handling.
 *
 * This class provides comprehensive error handling including request errors,
 * transport errors, system errors, and recovery strategies with proper
 * logging and statistics tracking.
 */
class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * Logger instance.
     */
    private LoggerInterface $logger;

    /**
     * Recovery strategies.
     *
     * @var array<string, array<int, callable[]>>
     */
    private array $recoveryStrategies = [];

    /**
     * Error statistics.
     *
     * @var array<string, mixed>
     */
    private array $errorStats = [
        'request_errors' => 0,
        'transport_errors' => 0,
        'system_errors' => 0,
        'recovery_attempts' => 0,
        'successful_recoveries' => 0,
        'failed_recoveries' => 0,
        'last_error_time' => null,
        'error_types' => [],
    ];

    /**
     * Maximum recovery attempts.
     */
    private int $maxRecoveryAttempts = 3;

    /**
     * Whether error reporting is enabled.
     */
    private bool $errorReporting = true;

    /**
     * Current recovery attempt count.
     */
    private int $currentRecoveryAttempts = 0;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger Logger instance
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->registerDefaultRecoveryStrategies();
    }

    /**
     * Handle an error that occurred during request processing.
     *
     * @param Throwable $error The error that occurred
     * @param null|JsonRpcRequest $request The request being processed (if available)
     * @return JsonRpcError The error response to send to the client
     */
    public function handleRequestError(Throwable $error, ?JsonRpcRequest $request = null): JsonRpcError
    {
        ++$this->errorStats['request_errors'];
        $this->updateErrorStats($error);

        if ($this->errorReporting) {
            $this->logger->error('Request processing error', [
                'error' => $error->getMessage(),
                'request_id' => $request?->getId(),
                'method' => $request?->getMethod(),
                'trace' => $error->getTraceAsString(),
            ]);
        }

        // Attempt recovery
        $recoveryContext = [
            'type' => 'request',
            'request' => $request,
        ];

        if ($this->attemptRecovery($error, $recoveryContext)) {
            $this->logger->info('Successfully recovered from request error');
        }

        // Convert error to JSON-RPC error response
        $errorData = $this->convertToErrorData($error);
        return new JsonRpcError(
            $request?->getId() ?? 'unknown',
            $errorData
        );
    }

    /**
     * Handle a transport-level error.
     *
     * @param Throwable $error The transport error
     * @param array<string, mixed> $context Additional context information
     */
    public function handleTransportError(Throwable $error, array $context = []): void
    {
        ++$this->errorStats['transport_errors'];
        $this->updateErrorStats($error);

        if ($this->errorReporting) {
            $this->logger->error('Transport error', [
                'error' => $error->getMessage(),
                'context' => $context,
                'trace' => $error->getTraceAsString(),
            ]);
        }

        // Attempt recovery
        $recoveryContext = array_merge($context, ['type' => 'transport']);
        $this->attemptRecovery($error, $recoveryContext);
    }

    /**
     * Handle a system-level error.
     *
     * @param Throwable $error The system error
     * @param array<string, mixed> $context Additional context information
     */
    public function handleSystemError(Throwable $error, array $context = []): void
    {
        ++$this->errorStats['system_errors'];
        $this->updateErrorStats($error);

        if ($this->errorReporting) {
            $this->logger->critical('System error', [
                'error' => $error->getMessage(),
                'context' => $context,
                'trace' => $error->getTraceAsString(),
            ]);
        }

        // Attempt recovery
        $recoveryContext = array_merge($context, ['type' => 'system']);
        $this->attemptRecovery($error, $recoveryContext);
    }

    /**
     * Register an error recovery strategy.
     *
     * @param string $errorType The type of error to handle
     * @param callable $strategy The recovery strategy callback
     * @param int $priority Priority (higher numbers execute first)
     */
    public function registerRecoveryStrategy(string $errorType, callable $strategy, int $priority = 0): void
    {
        if (! isset($this->recoveryStrategies[$errorType])) {
            $this->recoveryStrategies[$errorType] = [];
        }

        if (! isset($this->recoveryStrategies[$errorType][$priority])) {
            $this->recoveryStrategies[$errorType][$priority] = [];
        }

        $this->recoveryStrategies[$errorType][$priority][] = $strategy;

        // Sort by priority (highest first)
        krsort($this->recoveryStrategies[$errorType]);

        $this->logger->debug('Recovery strategy registered', [
            'error_type' => $errorType,
            'priority' => $priority,
        ]);
    }

    /**
     * Attempt to recover from an error.
     *
     * @param Throwable $error The error to recover from
     * @param array<string, mixed> $context Recovery context
     * @return bool True if recovery was successful, false otherwise
     */
    public function attemptRecovery(Throwable $error, array $context = []): bool
    {
        if ($this->currentRecoveryAttempts >= $this->maxRecoveryAttempts) {
            $this->logger->warning('Maximum recovery attempts reached', [
                'attempts' => $this->currentRecoveryAttempts,
                'max_attempts' => $this->maxRecoveryAttempts,
            ]);
            return false;
        }

        ++$this->currentRecoveryAttempts;
        ++$this->errorStats['recovery_attempts'];

        $errorType = get_class($error);
        $strategies = $this->getRecoveryStrategies($errorType);

        foreach ($strategies as $strategy) {
            try {
                $result = $strategy($error, $context, $this);
                if ($result === true) {
                    ++$this->errorStats['successful_recoveries'];
                    $this->currentRecoveryAttempts = 0; // Reset on success

                    $this->logger->info('Error recovery successful', [
                        'error_type' => $errorType,
                        'strategy' => 'callable',
                    ]);

                    return true;
                }
            } catch (Throwable $recoveryError) {
                $this->logger->warning('Recovery strategy failed', [
                    'error_type' => $errorType,
                    'recovery_error' => $recoveryError->getMessage(),
                ]);
            }
        }

        ++$this->errorStats['failed_recoveries'];

        $this->logger->warning('Error recovery failed', [
            'error_type' => $errorType,
            'attempts' => $this->currentRecoveryAttempts,
        ]);

        return false;
    }

    /**
     * Get error statistics.
     *
     * @return array<string, mixed> Error handling statistics
     */
    public function getErrorStats(): array
    {
        $stats = $this->errorStats;
        $stats['recovery_success_rate'] = $this->calculateRecoverySuccessRate();
        $stats['current_recovery_attempts'] = $this->currentRecoveryAttempts;
        $stats['max_recovery_attempts'] = $this->maxRecoveryAttempts;
        $stats['error_reporting_enabled'] = $this->errorReporting;

        return $stats;
    }

    /**
     * Set the maximum number of recovery attempts.
     *
     * @param int $maxAttempts Maximum recovery attempts
     */
    public function setMaxRecoveryAttempts(int $maxAttempts): void
    {
        if ($maxAttempts < 0) {
            throw ValidationError::invalidFieldValue(
                'maxAttempts',
                'Maximum recovery attempts must be non-negative'
            );
        }

        $this->maxRecoveryAttempts = $maxAttempts;

        $this->logger->debug('Max recovery attempts updated', [
            'max_attempts' => $maxAttempts,
        ]);
    }

    /**
     * Enable or disable error reporting.
     *
     * @param bool $enabled Whether error reporting is enabled
     */
    public function setErrorReporting(bool $enabled): void
    {
        $this->errorReporting = $enabled;

        $this->logger->debug('Error reporting updated', [
            'enabled' => $enabled,
        ]);
    }

    /**
     * Convert a throwable to ErrorData.
     *
     * @param Throwable $error The error to convert
     * @return ErrorData The structured error data
     */
    public function convertToErrorData(Throwable $error): ErrorData
    {
        // If it's already an MCP error, extract its ErrorData
        if ($error instanceof McpError) {
            return $error->getError();
        }

        // Map common exception types to MCP error codes
        $code = $this->mapExceptionToErrorCode($error);
        $message = $error->getMessage();
        $data = [
            'type' => get_class($error),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ];

        return new ErrorData($code, $message, $data);
    }

    /**
     * Update error statistics.
     *
     * @param Throwable $error The error to track
     */
    private function updateErrorStats(Throwable $error): void
    {
        $this->errorStats['last_error_time'] = time();

        $errorType = get_class($error);
        if (! isset($this->errorStats['error_types'][$errorType])) {
            $this->errorStats['error_types'][$errorType] = 0;
        }
        ++$this->errorStats['error_types'][$errorType];
    }

    /**
     * Get recovery strategies for an error type.
     *
     * @param string $errorType The error type
     * @return callable[] Array of recovery strategies
     */
    private function getRecoveryStrategies(string $errorType): array
    {
        $strategies = [];

        // Get strategies for specific error type
        if (isset($this->recoveryStrategies[$errorType])) {
            foreach ($this->recoveryStrategies[$errorType] as $priorityStrategies) {
                $strategies = array_merge($strategies, $priorityStrategies);
            }
        }

        // Get generic strategies
        if (isset($this->recoveryStrategies['*'])) {
            foreach ($this->recoveryStrategies['*'] as $priorityStrategies) {
                $strategies = array_merge($strategies, $priorityStrategies);
            }
        }

        return $strategies;
    }

    /**
     * Calculate recovery success rate.
     *
     * @return float Recovery success rate (0.0 to 1.0)
     */
    private function calculateRecoverySuccessRate(): float
    {
        $totalAttempts = $this->errorStats['recovery_attempts'];
        if ($totalAttempts === 0) {
            return 0.0;
        }

        return $this->errorStats['successful_recoveries'] / $totalAttempts;
    }

    /**
     * Map exception types to MCP error codes.
     *
     * @param Throwable $error The error to map
     * @return int The appropriate error code
     */
    private function mapExceptionToErrorCode(Throwable $error): int
    {
        return match (true) {
            $error instanceof ValidationError => ErrorCodes::VALIDATION_ERROR,
            $error instanceof ProtocolError => ErrorCodes::PROTOCOL_ERROR,
            $error instanceof InvalidArgumentException => ErrorCodes::INVALID_PARAMS,
            $error instanceof RuntimeException => ErrorCodes::INTERNAL_ERROR,
            $error instanceof LogicException => ErrorCodes::INTERNAL_ERROR,
            default => ErrorCodes::INTERNAL_ERROR,
        };
    }

    /**
     * Register default recovery strategies.
     */
    private function registerDefaultRecoveryStrategies(): void
    {
        // Generic retry strategy
        $this->registerRecoveryStrategy('*', function (Throwable $error, array $context, ErrorHandlerInterface $handler) {
            // Simple retry logic - could be enhanced based on error type
            if (isset($context['retry_count']) && $context['retry_count'] > 2) {
                return false;
            }

            // For transport errors, try to reconnect
            if (isset($context['type']) && $context['type'] === 'transport') {
                $this->logger->info('Attempting transport recovery');
                // Transport recovery logic would go here
                return false; // For now, don't attempt automatic recovery
            }

            return false;
        }, 0);

        // Memory cleanup strategy
        $this->registerRecoveryStrategy(Error::class, function (Throwable $error, array $context, ErrorHandlerInterface $handler) {
            // Check if it's specifically a memory error
            if (strpos($error->getMessage(), 'memory') !== false || strpos($error->getMessage(), 'Memory') !== false) {
                $this->logger->warning('Attempting memory cleanup recovery');

                // Force garbage collection
                gc_collect_cycles();

                // Clear any caches if available
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                }

                return true; // Assume memory cleanup helps
            }

            return false;
        }, 100);

        // Validation error strategy
        $this->registerRecoveryStrategy(ValidationError::class, function (Throwable $error, array $context, ErrorHandlerInterface $handler) {
            // Validation errors typically don't need recovery, just proper error response
            $this->logger->debug('Validation error handled, no recovery needed');
            return true;
        }, 50);
    }
}
