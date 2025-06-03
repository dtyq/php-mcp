<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Http;

use Dtyq\PhpMcp\Server\Transports\Core\AbstractTransport;
use Dtyq\PhpMcp\Server\Transports\Core\TransportMetadata;
use Dtyq\PhpMcp\Server\Transports\Http\Event\HttpTransportAuthenticatedEvent;
use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Auth\NullAuthenticator;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class HttpTransport extends AbstractTransport
{
    private SessionManagerInterface $sessionManager;

    private AuthenticatorInterface $authenticator;

    public function __construct(
        Application $application,
        TransportMetadata $transportMetadata,
        ?SessionManagerInterface $sessionManager = null,
        ?AuthenticatorInterface $authenticator = null
    ) {
        parent::__construct($application, $transportMetadata);

        if (is_null($sessionManager)) {
            if ($application->has(SessionManagerInterface::class)) {
                $sessionManager = $application->get(SessionManagerInterface::class);
            } else {
                // Default to file-based session manager if none provided
                $sessionManager = new FileSessionManager($application->getPacker());
            }
        }
        if (is_null($authenticator)) {
            if ($application->has(NullAuthenticator::class)) {
                $authenticator = $application->get(NullAuthenticator::class);
            } else {
                // Default to null authenticator if none provided
                $authenticator = new NullAuthenticator();
            }
        }

        $this->sessionManager = $sessionManager;
        $this->authenticator = $authenticator;
    }

    public function handleRequest(RequestInterface $request): ResponseInterface
    {
        $method = strtoupper($request->getMethod());

        if ($method !== 'POST') {
            return new Response(405, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept, Mcp-Session-Id',
            ], json_encode(['error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE));
        }

        try {
            // Get session ID from header
            $sessionId = $this->getSessionIdFromRequest($request);

            // Parse the request body to check if it's an initialization request
            $body = $request->getBody()->getContents();
            $jsonData = json_decode($body, true);

            $headers = [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept, Mcp-Session-Id',
            ];

            $isInitializeRequest = $this->isInitializeRequest($jsonData);

            // Handle session validation
            if (! $isInitializeRequest) {
                // Non-initialization requests must have valid session ID
                if (! $sessionId || ! $this->sessionManager->isValidSession($sessionId)) {
                    return new Response(400, [
                        'Content-Type' => 'application/json',
                    ], json_encode([
                        'jsonrpc' => '2.0',
                        'error' => [
                            'code' => -32600,
                            'message' => 'Invalid or missing Mcp-Session-Id header',
                        ],
                        'id' => $jsonData['id'] ?? null,
                    ], JSON_UNESCAPED_UNICODE));
                }
                $this->sessionManager->updateSessionActivity($sessionId);

                $historyTransportMetadata = $this->sessionManager->getSessionMetadata($sessionId)['transport_metadata'] ?? null;
                if ($historyTransportMetadata instanceof TransportMetadata) {
                    $this->transportMetadata = $historyTransportMetadata;
                }
            } else {
                $authInfo = $this->authenticator->authenticate();
                $this->app->getEventDispatcher()->dispatch(new HttpTransportAuthenticatedEvent($authInfo, $this->transportMetadata));

                $sessionId = $this->sessionManager->createSession();
                $this->sessionManager->setSessionMetadata($sessionId, ['transport_metadata' => $this->transportMetadata]);
                $headers['Mcp-Session-Id'] = $sessionId;
            }

            $this->logger->info('Handling request', [
                'method' => $method,
                'sessionId' => $sessionId,
                'isInitialize' => $isInitializeRequest,
            ]);

            $responseBody = $this->handleMessage($body);

            return new Response(200, $headers, $responseBody);
        } catch (Throwable $exception) {
            return new Response(500, [
                'Content-Type' => 'application/json',
            ], json_encode(['error' => 'Error: ' . $exception->getMessage()], JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Get session manager instance.
     */
    public function getSessionManager(): SessionManagerInterface
    {
        return $this->sessionManager;
    }

    /**
     * Terminate a session (delegated to session manager).
     */
    public function terminateSession(string $sessionId): bool
    {
        $terminated = $this->sessionManager->terminateSession($sessionId);
        if ($terminated) {
            $this->logger->info('Session terminated', ['sessionId' => $sessionId]);
        }
        return $terminated;
    }

    /**
     * Get all active sessions (delegated to session manager).
     *
     * @return string[]
     */
    public function getActiveSessions(): array
    {
        return $this->sessionManager->getActiveSessions();
    }

    public function start(): void
    {
    }

    public function stop(): void
    {
    }

    public function sendMessage(string $message): void
    {
    }

    protected function getTransportType(): string
    {
        return ProtocolConstants::TRANSPORT_TYPE_HTTP;
    }

    /**
     * Get session ID from request headers.
     */
    private function getSessionIdFromRequest(RequestInterface $request): ?string
    {
        $sessionHeaders = $request->getHeader('Mcp-Session-Id');
        if (empty($sessionHeaders)) {
            // Try alternative header name (some clients might use different case)
            $sessionHeaders = $request->getHeader('MCP-Session-ID');
        }

        return ! empty($sessionHeaders) ? $sessionHeaders[0] : null;
    }

    /**
     * Check if the request is an initialization request.
     *
     * @param mixed $jsonData
     */
    private function isInitializeRequest($jsonData): bool
    {
        if (! is_array($jsonData)) {
            return false;
        }

        // Handle single request
        if (isset($jsonData['method'])) {
            return $jsonData['method'] === 'initialize';
        }

        // Handle batch requests
        if (isset($jsonData[0])) {
            foreach ($jsonData as $request) {
                if (isset($request['method']) && $request['method'] === 'initialize') {
                    return true;
                }
            }
        }

        return false;
    }
}
