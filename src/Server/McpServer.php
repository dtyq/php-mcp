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
use Dtyq\PhpMcp\Server\Transports\Stdio\StdioTransport;
use Dtyq\PhpMcp\Server\Transports\TransportMetadata;
use Dtyq\PhpMcp\Shared\Exceptions\SystemException;
use Dtyq\PhpMcp\Shared\Kernel\Application;

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

    public function registerTool(RegisteredTool $tool): self
    {
        $this->toolManager->register($tool);
        return $this;
    }

    public function registerPrompt(RegisteredPrompt $prompt): self
    {
        $this->promptManager->register($prompt);
        return $this;
    }

    public function registerResource(RegisteredResource $resource): self
    {
        $this->resourceManager->register($resource);
        return $this;
    }

    public function registerTemplate(RegisteredResourceTemplate $template): self
    {
        $this->resourceManager->registerTemplate($template);
        return $this;
    }

    public function stdio(): void
    {
        $this->start('stdio');
    }

    public function start(string $transport): void
    {
        $transportMetadata = new TransportMetadata(
            $this->name,
            $this->version,
            '',
            $this->toolManager,
            $this->promptManager,
            $this->resourceManager
        );
        switch ($transport) {
            case 'stdio':
                $transport = new StdioTransport(
                    $this->application,
                    $transportMetadata
                );
                $transport->handleSubprocessLifecycle();
                break;
            default:
                throw new SystemException('Transport not set');
        }
        $transport->start();
    }
}
