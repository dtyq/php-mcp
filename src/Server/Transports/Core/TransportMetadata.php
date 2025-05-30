<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Transports\Core;

use Dtyq\PhpMcp\Server\FastMcp\Prompts\PromptManager;
use Dtyq\PhpMcp\Server\FastMcp\Resources\ResourceManager;
use Dtyq\PhpMcp\Server\FastMcp\Tools\ToolManager;

class TransportMetadata
{
    protected string $name;

    protected string $version;

    protected string $instructions = '';

    protected ToolManager $toolManager;

    protected PromptManager $promptManager;

    protected ResourceManager $resourceManager;

    public function __construct(
        string $name,
        string $version,
        string $instructions,
        ToolManager $toolManager,
        PromptManager $promptManager,
        ResourceManager $resourceManager
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->instructions = $instructions;
        $this->toolManager = $toolManager;
        $this->promptManager = $promptManager;
        $this->resourceManager = $resourceManager;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getInstructions(): string
    {
        return $this->instructions;
    }

    public function getToolManager(): ToolManager
    {
        return $this->toolManager;
    }

    public function getPromptManager(): PromptManager
    {
        return $this->promptManager;
    }

    public function getResourceManager(): ResourceManager
    {
        return $this->resourceManager;
    }
}
