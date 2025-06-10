<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Core;

use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\ProtocolError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;
use Dtyq\PhpMcp\Types\Core\JsonRpcError;
use Dtyq\PhpMcp\Types\Core\JsonRpcRequest;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponse;
use Dtyq\PhpMcp\Types\Core\JsonRpcResponseInterface;
use Dtyq\PhpMcp\Types\Core\RequestInterface;
use Exception;
use InvalidArgumentException;

/**
 * Abstract base class for MCP client session implementations.
 *
 * This class provides common functionality and utilities that all session
 * implementations can use, including request ID generation, message
 * serialization, and timeout handling.
 */
abstract class AbstractSession implements SessionInterface
{
    protected TransportInterface $transport;

    protected bool $initialized = false;

    protected int $nextRequestId = 1;

    /** @var array<string, float> Map of pending request IDs to their start times */
    protected array $pendingRequests = [];

    /** @var float Default timeout for requests in seconds */
    protected float $defaultTimeout = 30.0;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function close(): void
    {
        if ($this->transport->isConnected()) {
            $this->transport->disconnect();
        }

        $this->initialized = false;
        $this->pendingRequests = [];
    }

    /**
     * Send a request and wait for response.
     *
     * This method handles the low-level request/response cycle including
     * ID generation, timeout handling, and response correlation.
     *
     * @param RequestInterface $request The request to send
     * @param null|float $timeout Optional timeout override
     * @return JsonRpcResponse The server response
     * @throws ProtocolError If protocol error occurs
     * @throws TransportError If transport error occurs
     * @throws McpError If server returns an error response
     */
    protected function sendRequestAndWaitForResponse(
        RequestInterface $request,
        ?float $timeout = null
    ): JsonRpcResponse {
        // Generate unique request ID
        $requestId = $this->generateRequestId();
        $request->setId($requestId);

        // Create JsonRpcRequest from RequestInterface
        $jsonRpcRequest = new JsonRpcRequest($request->getMethod(), $request->getParams(), $requestId);

        // Serialize and send request
        $message = JsonUtils::encode($jsonRpcRequest->toJsonRpc());
        $this->transport->send($message);

        // Track pending request
        $this->pendingRequests[$requestId] = microtime(true);

        try {
            // Wait for response
            return $this->waitForResponse($requestId, $timeout ?? $this->defaultTimeout);
        } finally {
            // Cleanup pending request
            unset($this->pendingRequests[$requestId]);
        }
    }

    /**
     * Wait for a response to a specific request.
     *
     * @param string $requestId The request ID to wait for
     * @param float $timeout Maximum time to wait in seconds
     * @return JsonRpcResponse The received response
     * @throws ProtocolError If timeout occurs or invalid response received
     * @throws TransportError If transport error occurs
     * @throws McpError If server returns an error response
     */
    protected function waitForResponse(string $requestId, float $timeout): JsonRpcResponse
    {
        $startTime = microtime(true);
        $endTime = $startTime + $timeout;

        while (microtime(true) < $endTime) {
            $remainingTimeout = $endTime - microtime(true);

            // Try to receive a message (convert float to int for transport interface)
            $timeoutInt = $remainingTimeout > 0 ? (int) ceil($remainingTimeout) : 1;
            $message = $this->transport->receive($timeoutInt);

            if ($message === null) {
                continue; // Timeout on this attempt, try again if overall timeout not reached
            }

            try {
                $response = $this->parseResponse($message);

                // Check if this is the response we're waiting for
                if ($response->getId() !== null && (string) $response->getId() === $requestId) {
                    // If it's an error response, throw McpError
                    if ($response->isError() && $response instanceof JsonRpcError) {
                        throw new McpError($response->getError());
                    }

                    // Return successful response
                    if ($response instanceof JsonRpcResponse) {
                        return $response;
                    }

                    throw new ProtocolError('Unexpected response type');
                }

                // Handle unexpected message
                $this->handleUnexpectedMessage($response);
            } catch (McpError $e) {
                // Re-throw McpError as-is
                throw $e;
            } catch (Exception $e) {
                throw new ProtocolError('Failed to parse response: ' . $e->getMessage());
            }
        }

        // Timeout exceeded
        throw new ProtocolError(
            "Request timeout after {$timeout} seconds for request ID: {$requestId}"
        );
    }

    /**
     * Parse a raw message into a JsonRpcResponseInterface.
     *
     * @param string $message The raw message to parse
     * @return JsonRpcResponseInterface The parsed response (either success or error)
     * @throws ProtocolError If parsing fails
     */
    protected function parseResponse(string $message): JsonRpcResponseInterface
    {
        try {
            $data = JsonUtils::decode($message);

            // Check if this is an error response
            if (isset($data['error'])) {
                return JsonRpcError::fromArray($data);
            }

            // Otherwise it should be a successful response
            return JsonRpcResponse::fromArray($data);
        } catch (Exception $e) {
            throw new ProtocolError('Failed to parse JSON-RPC response: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique request ID.
     *
     * @return string A unique request identifier
     */
    protected function generateRequestId(): string
    {
        return (string) $this->nextRequestId++;
    }

    /**
     * Set the default timeout for requests.
     *
     * @param float $timeout Timeout in seconds
     */
    protected function setDefaultTimeout(float $timeout): void
    {
        if ($timeout <= 0) {
            throw new InvalidArgumentException('Timeout must be greater than 0');
        }
        $this->defaultTimeout = $timeout;
    }

    /**
     * Get the current default timeout.
     *
     * @return float Timeout in seconds
     */
    protected function getDefaultTimeout(): float
    {
        return $this->defaultTimeout;
    }

    /**
     * Handle unexpected messages received while waiting for a specific response.
     *
     * This method can be overridden by concrete implementations to handle
     * server-initiated requests or notifications.
     *
     * @param JsonRpcResponseInterface $message The unexpected message
     */
    protected function handleUnexpectedMessage(JsonRpcResponseInterface $message): void
    {
        // Default implementation: log and ignore
        // TODO: Add logging when logger is available
        // Could also store these messages for later processing
    }

    /**
     * Check if the session is in a valid state for the given operation.
     *
     * @param string $operation The operation being attempted
     * @param bool $requireInitialized Whether the session must be initialized
     * @throws ProtocolError If session state is invalid
     */
    protected function validateSessionState(string $operation, bool $requireInitialized = true): void
    {
        if (! $this->transport->isConnected()) {
            throw new ProtocolError(
                "Cannot perform '{$operation}': transport not connected"
            );
        }

        if ($requireInitialized && ! $this->initialized) {
            throw new ProtocolError(
                "Cannot perform '{$operation}': session not initialized"
            );
        }
    }

    /**
     * Mark the session as initialized.
     *
     * This should be called by concrete implementations after
     * successful session initialization.
     */
    protected function markAsInitialized(): void
    {
        $this->initialized = true;
    }
}
