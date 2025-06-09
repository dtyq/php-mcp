<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations;

use Attribute;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\SchemaUtils;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
class McpTool extends McpAnnotation
{
    protected string $name = '';

    protected string $description = '';

    /** @var array<string, mixed> */
    protected array $inputSchema = [];

    protected string $server = '';

    protected string $version = '';

    protected bool $enabled = true;

    /**
     * @param array<string, mixed> $inputSchema
     */
    public function __construct(
        string $name = '',
        string $description = '',
        array $inputSchema = [],
        string $server = '',
        string $version = '',
        bool $enabled = true
    ) {
        if ($name !== '' && ! preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new ValidationError('Tool name must be alphanumeric and underscores.');
        }
        $this->name = $name;
        $this->description = $description;
        $this->inputSchema = $inputSchema;
        $this->server = $server;
        $this->version = $version;
        $this->enabled = $enabled;
    }

    public function getName(): string
    {
        if ($this->name === '') {
            $this->name = $this->method;
        }
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
        if (empty($this->inputSchema)) {
            $this->inputSchema = SchemaUtils::generateInputSchemaByClassMethod($this->class, $this->method);
        }
        return $this->inputSchema;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
