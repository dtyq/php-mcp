<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf;

use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\McpCollector;
use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Server\Transports\Http\SessionManagerInterface;
use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class HyperfMcpServer
{
    /**
     * @var array<string, <string, McpServer>>
     */
    protected array $servers = [];

    protected ContainerInterface $container;

    protected AuthenticatorInterface $authenticator;

    protected SessionManagerInterface $sessionManager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->authenticator = $container->get(AuthenticatorInterface::class);
        $this->sessionManager = $container->get(SessionManagerInterface::class);
    }

    public function handle(string $server, string $version = '1.0.0'): ResponseInterface
    {
        $mcpServer = $this->servers[$server][$version] ?? null;
        if (! $mcpServer instanceof McpServer) {
            $mcpServer = $this->createMcpServer($server, $version);
            $this->servers[$server][$version] = $mcpServer;
        }
        $request = $this->container->get(RequestInterface::class);
        return $mcpServer->http($request, $this->sessionManager, $this->authenticator);
    }

    protected function createMcpServer(string $server, string $version = '1.0.0'): McpServer
    {
        $app = new Application($this->container);
        $mcpServer = new McpServer($server, $version, $app);

        $this->addAnnotationTools($mcpServer, $server);
        $this->addAnnotationPrompts($mcpServer, $server);
        $this->addAnnotationResources($mcpServer, $server);

        return $mcpServer;
    }

    protected function addAnnotationTools(McpServer $mcpServer, string $server = ''): void
    {
        $registeredTools = McpCollector::getTools($server);
        foreach ($registeredTools as $registeredTool) {
            $mcpServer->registerTool($registeredTool);
        }
    }

    protected function addAnnotationPrompts(McpServer $mcpServer, string $server = ''): void
    {
        $registeredPrompts = McpCollector::getPrompts($server);
        foreach ($registeredPrompts as $registeredPrompt) {
            $mcpServer->registerPrompt($registeredPrompt);
        }
    }

    protected function addAnnotationResources(McpServer $mcpServer, string $server = ''): void
    {
        $registeredResources = McpCollector::getResources($server);
        foreach ($registeredResources as $registeredResource) {
            $mcpServer->registerResource($registeredResource);
        }
    }
}
