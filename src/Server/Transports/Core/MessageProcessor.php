<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core;

use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Exception;
use InvalidArgumentException;
use stdClass;

/**
 * Processes JSON-RPC messages and integrates with FastMcp components.
 *
 * This class handles the core message processing logic for MCP transports,
 * routing messages to appropriate FastMcp managers and ensuring compliance
 * with MCP 2025-03-26 specification.
 */
class MessageProcessor
{
    private TransportMetadata $transportMetadata;

    private LoggerProxy $logger;

    public function __construct(Application $app, TransportMetadata $transportMetadata)
    {
        $this->transportMetadata = $transportMetadata;
        $this->logger = $app->getLogger();
    }

    /**
     * Process a JSON-RPC message and return response if needed.
     *
     * @param string $jsonRpc The JSON-RPC message string
     * @return null|string The response message, or null if no response needed
     * @throws TransportError If message processing fails
     */
    public function processJsonRpc(string $jsonRpc): ?string
    {
        try {
            // Validate UTF-8 encoding (MCP requirement)
            if (! $this->validateUtf8($jsonRpc)) {
                throw TransportError::malformedMessage('json-rpc', 'Message is not valid UTF-8');
            }

            // Parse JSON
            $decoded = json_decode($jsonRpc, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw TransportError::malformedMessage('json-rpc', 'Invalid JSON: ' . json_last_error_msg());
            }

            // Handle batch or single message
            if (is_array($decoded) && isset($decoded[0])) {
                return $this->handleBatch($decoded);
            }
            return $this->handleSingleMessage($decoded);
        } catch (Exception $e) {
            $this->logger->error('Message processing failed', [
                'message' => $jsonRpc,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a batch of JSON-RPC messages.
     *
     * @param array $batch Array of JSON-RPC messages
     * @return null|string The batch response, or null if no responses needed
     */
    public function handleBatch(array $batch): ?string
    {
        $responses = [];

        foreach ($batch as $message) {
            if (! is_array($message)) {
                throw TransportError::malformedMessage('json-rpc', 'Invalid batch message format');
            }

            if (isset($message['method'], $message['id'])) {
                // Request in batch - collect response
                $response = $this->handleRequest($message);
                if ($response !== null) {
                    $responses[] = $response;
                }
            } elseif (isset($message['method'])) {
                // Notification in batch - no response
                $this->handleNotification($message);
            } elseif (isset($message['result']) || isset($message['error'])) {
                // Response in batch - handle but don't respond
                $this->handleResponse($message);
            }
        }

        return empty($responses) ? null : json_encode($responses);
    }

    /**
     * Handle a JSON-RPC request.
     *
     * @param array $request The request message
     * @return array The response message
     */
    public function handleRequest(array $request): array
    {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;

        try {
            $result = $this->routeRequest($method, $params);

            return [
                'jsonrpc' => '2.0',
                'result' => $result,
                'id' => $id,
            ];
        } catch (Exception $e) {
            $this->logger->error('Request handling failed', [
                'method' => $method,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return [
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32603,
                    'message' => 'Internal error',
                    'data' => $e->getMessage(),
                ],
                'id' => $id,
            ];
        }
    }

    /**
     * Handle a JSON-RPC notification.
     *
     * @param array $notification The notification message
     */
    public function handleNotification(array $notification): void
    {
        $method = $notification['method'] ?? '';
        $params = $notification['params'] ?? [];

        try {
            $this->routeNotification($method, $params);
        } catch (Exception $e) {
            $this->logger->error('Notification handling failed', [
                'method' => $method,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a JSON-RPC response.
     *
     * @param array $response The response message
     */
    public function handleResponse(array $response): void
    {
        $id = $response['id'] ?? null;

        if (isset($response['result'])) {
            $this->logger->debug('Received response', [
                'id' => $id,
                'result' => $response['result'],
            ]);
        } elseif (isset($response['error'])) {
            $this->logger->warning('Received error response', [
                'id' => $id,
                'error' => $response['error'],
            ]);
        }
    }

    /**
     * Validate that a message is valid UTF-8.
     *
     * @param string $message The message to validate
     * @return bool True if valid UTF-8, false otherwise
     */
    public function validateUtf8(string $message): bool
    {
        return mb_check_encoding($message, 'UTF-8');
    }

    /**
     * Validate a JSON-RPC message structure.
     *
     * @param string $message The message to validate
     * @return bool True if valid, false otherwise
     */
    public function validateMessage(string $message): bool
    {
        if (! $this->validateUtf8($message)) {
            return false;
        }

        $decoded = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Basic JSON-RPC validation
        if (is_array($decoded)) {
            if (isset($decoded[0])) {
                // Batch
                foreach ($decoded as $item) {
                    if (! $this->validateSingleMessage($item)) {
                        return false;
                    }
                }
            } else {
                // Single message
                return $this->validateSingleMessage($decoded);
            }
        }

        return true;
    }

    /**
     * Handle a single JSON-RPC message.
     *
     * @param array $message The decoded JSON-RPC message
     * @return null|string The response message, or null if no response needed
     */
    private function handleSingleMessage(array $message): ?string
    {
        if (isset($message['method'])) {
            if (isset($message['id'])) {
                // Request - needs response
                $response = $this->handleRequest($message);
                return json_encode($response);
            }
            // Notification - no response
            $this->handleNotification($message);
            return null;
        }
        if (isset($message['result']) || isset($message['error'])) {
            // Response - handle but don't respond
            $this->handleResponse($message);
            return null;
        }
        throw TransportError::malformedMessage('json-rpc', 'Invalid JSON-RPC message structure');
    }

    /**
     * Route a request to the appropriate FastMcp manager.
     *
     * @param string $method The method name
     * @param array $params The method parameters
     * @return mixed The result
     */
    private function routeRequest(string $method, array $params)
    {
        switch ($method) {
            case 'initialize':
                $capabilities = [
                    'instructions' => $this->transportMetadata->getInstructions() ?: '',
                    'logging' => new stdClass(),
                ];
                if ($this->transportMetadata->getToolManager()->count() > 0) {
                    $capabilities['tools'] = [
                        'listChanged' => false,
                    ];
                }
                if ($this->transportMetadata->getPromptManager()->count() > 0) {
                    $capabilities['prompts'] = [
                        'listChanged' => false,
                    ];
                }
                if ($this->transportMetadata->getResourceManager()->count() > 0) {
                    $capabilities['resources'] = [
                        'listChanged' => false,
                    ];
                }

                return [
                    'protocolVersion' => ProtocolConstants::LATEST_PROTOCOL_VERSION,
                    'capabilities' => $capabilities,
                    'serverInfo' => [
                        'name' => $this->transportMetadata->getName() ?: 'php-mcp-server',
                        'version' => $this->transportMetadata->getVersion() ?: '1.0.0',
                    ],
                ];

            case 'tools/list':
                $tools = $this->transportMetadata->getToolManager()->getAll();
                return [
                    'tools' => array_map(function ($registeredTool) {
                        return $registeredTool->getTool()->toArray();
                    }, $tools),
                ];

            case 'tools/call':
                $name = $params['name'] ?? '';
                $arguments = $params['arguments'] ?? [];
                $result = $this->transportMetadata->getToolManager()->execute($name, $arguments);

                // Wrap result in proper tool result format
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => is_string($result) ? $result : json_encode($result),
                        ],
                    ],
                ];

            case 'prompts/list':
                $prompts = $this->transportMetadata->getPromptManager()->getAll();
                return [
                    'prompts' => array_map(function ($registeredPrompt) {
                        return $registeredPrompt->getPrompt()->toArray();
                    }, $prompts),
                ];

            case 'prompts/get':
                $name = $params['name'] ?? '';
                $arguments = $params['arguments'] ?? [];
                $result = $this->transportMetadata->getPromptManager()->execute($name, $arguments);

                // Return the GetPromptResult as array
                return $result->toArray();
            case 'resources/list':
                $resources = $this->transportMetadata->getResourceManager()->getAll();
                return [
                    'resources' => array_map(function ($registeredResource) {
                        return $registeredResource->getResource()->toArray();
                    }, $resources),
                ];

            case 'resources/read':
                $uri = $params['uri'] ?? '';
                $content = $this->transportMetadata->getResourceManager()->getContent($uri);

                // Return the resource content as array
                return [
                    'contents' => [$content->toArray()],
                ];

            default:
                throw new InvalidArgumentException("Unknown method: {$method}");
        }
    }

    /**
     * Route a notification to the appropriate handler.
     *
     * @param string $method The method name
     * @param array $params The method parameters
     */
    private function routeNotification(string $method, array $params): void
    {
        switch ($method) {
            case 'notifications/initialized':
                $this->logger->info('Client initialized');
                break;
            case 'notifications/cancelled':
                $id = $params['id'] ?? null;
                $this->logger->info('Request cancelled', ['id' => $id]);
                break;
            default:
                $this->logger->warning('Unknown notification method', ['method' => $method]);
        }
    }

    /**
     * Validate a single JSON-RPC message.
     *
     * @param array $message The message to validate
     * @return bool True if valid, false otherwise
     */
    private function validateSingleMessage(array $message): bool
    {
        // Must have jsonrpc version
        if (! isset($message['jsonrpc']) || $message['jsonrpc'] !== '2.0') {
            return false;
        }

        // Request or notification must have method
        if (isset($message['method'])) {
            return is_string($message['method']);
        }

        // Response must have result or error, and id
        if (isset($message['result']) || isset($message['error'])) {
            return isset($message['id']);
        }

        return false;
    }
}
