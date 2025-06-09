<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server;

use Dtyq\PhpMcp\Server\FastMcp\Prompts\PromptManager;
use Dtyq\PhpMcp\Server\FastMcp\Prompts\RegisteredPrompt;
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResourceTemplate;
use Dtyq\PhpMcp\Server\FastMcp\Resources\ResourceManager;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Server\FastMcp\Tools\ToolManager;
use Dtyq\PhpMcp\Server\Transports\Core\TransportMetadata;
use Dtyq\PhpMcp\Server\Transports\Http\HttpTransport;
use Dtyq\PhpMcp\Server\Transports\Http\SessionManagerInterface;
use Dtyq\PhpMcp\Server\Transports\Stdio\StdioTransport;
use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * MCP Server - Core business logic for Model Context Protocol.
 *
 * This class manages MCP-specific functionality like tools, prompts, and resources.
 * It does not handle transport concerns - those are handled by separate classes
 * like HttpServer for HTTP transport or StdioTransport for stdio transport.
 */
class McpServer
{
    private string $name;

    private string $version;

    private Application $application;

    private ToolManager $toolManager;

    private PromptManager $promptManager;

    private ResourceManager $resourceManager;

    public function __construct(
        string $name,
        string $version,
        Application $application
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->application = $application;

        $this->toolManager = new ToolManager();
        $this->promptManager = new PromptManager();
        $this->resourceManager = new ResourceManager();
    }

    /**
     * Register a tool with the MCP server.
     *
     * @param RegisteredTool $tool Tool to register
     */
    public function registerTool(RegisteredTool $tool): self
    {
        $this->toolManager->register($tool);
        return $this;
    }

    /**
     * Register a prompt with the MCP server.
     *
     * @param RegisteredPrompt $prompt Prompt to register
     */
    public function registerPrompt(RegisteredPrompt $prompt): self
    {
        $this->promptManager->register($prompt);
        return $this;
    }

    /**
     * Register a resource with the MCP server.
     *
     * @param RegisteredResource $resource Resource to register
     */
    public function registerResource(RegisteredResource $resource): self
    {
        $this->resourceManager->register($resource);
        return $this;
    }

    /**
     * Register a resource template with the MCP server.
     *
     * @param RegisteredResourceTemplate $template Template to register
     */
    public function registerTemplate(RegisteredResourceTemplate $template): self
    {
        $this->resourceManager->registerTemplate($template);
        return $this;
    }

    /**
     * Start stdio transport for command-line usage.
     *
     * This method starts the stdio transport which is commonly used
     * for command-line MCP servers.
     */
    public function stdio(): void
    {
        $transportMetadata = $this->createTransportMetadata();
        $transport = new StdioTransport(
            $this->application,
            $transportMetadata
        );
        $transport->handleSubprocessLifecycle();
        $transport->start();
    }

    public function http(
        RequestInterface $request,
        ?SessionManagerInterface $sessionManager = null,
        ?AuthenticatorInterface $authenticator = null
    ): ResponseInterface {
        $transportMetadata = $this->createTransportMetadata();
        $transport = new HttpTransport(
            $this->application,
            $transportMetadata,
            $sessionManager,
            $authenticator
        );
        return $transport->handleRequest($request, $this->name, $this->version);
    }

    /**
     * Get the server name.
     *
     * @return string Server name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the server version.
     *
     * @return string Server version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    protected function createTransportMetadata(): TransportMetadata
    {
        return new TransportMetadata(
            $this->name,
            $this->version,
            '',
            $this->toolManager,
            $this->promptManager,
            $this->resourceManager
        );
    }
}
