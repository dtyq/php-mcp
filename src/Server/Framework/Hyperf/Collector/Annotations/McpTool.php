<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations;

use Attribute;
use Dtyq\PhpMcp\Shared\Exceptions\ToolError;
use Dtyq\PhpMcp\Shared\Utilities\ToolUtils;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
class McpTool extends AbstractAnnotation
{
    protected string $name = '';

    protected string $description = '';

    /** @var array<string, mixed> */
    protected array $inputSchema = [];

    protected string $group = '';

    protected bool $enabled = true;

    /**
     * @param array<string, mixed> $inputSchema
     */
    public function __construct(
        string $name = '',
        string $description = '',
        array $inputSchema = [],
        string $group = '',
        bool $enabled = true,
    ) {
        if ($name !== '' && ! preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new ToolError('Tool name must be alphanumeric and underscores.');
        }
        $this->name = $name;
        $this->description = $description;
        $this->inputSchema = $inputSchema;
        $this->group = $group;
        $this->enabled = $enabled;
    }

    public function collectMethod(string $className, ?string $target): void
    {
        if ($this->name === '') {
            $this->name = $target;
        }
        if (empty($this->inputSchema)) {
            $this->inputSchema = ToolUtils::generateInputSchema($className, $target);
        }
        parent::collectMethod($className, $target);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>
     */
    public function getInputSchema(): array
    {
        return $this->inputSchema;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
