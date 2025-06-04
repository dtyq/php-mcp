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
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HyperfMcpServer
{
    public function handler(string $group = ''): ResponseInterface
    {
        $container = ApplicationContext::getContainer();
        $request = $container->get(RequestInterface::class);

        $authenticator = $container->get(AuthenticatorInterface::class);
        $sessionManager = $container->get(SessionManagerInterface::class);
        $app = new Application($container);
        $mcpServer = new McpServer('HyperfMcpServer', '1.0.0', $app);

        $this->addAnnotationTools($mcpServer, $group);

        return $mcpServer->http($request, $sessionManager, $authenticator);
    }

    protected function addAnnotationTools(McpServer $mcpServer, string $group = ''): void
    {
        $registeredTools = McpCollector::getTools($group);
        foreach ($registeredTools as $registeredTool) {
            $mcpServer->registerTool($registeredTool);
        }
    }
}
