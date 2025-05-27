<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Tools;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;

/**
 * Additional properties describing a Tool to clients.
 *
 * NOTE: all properties in ToolAnnotations are **hints**.
 * They are not guaranteed to provide a faithful description of
 * tool behavior (including descriptive properties like `title`).
 *
 * Clients should never make tool use decisions based on ToolAnnotations
 * received from untrusted servers.
 */
class ToolAnnotations
{
    /** @var null|string A human-readable title for the tool */
    private ?string $title;

    /** @var null|bool If true, the tool does not modify its environment */
    private ?bool $readOnlyHint;

    /** @var null|bool If true, the tool may perform destructive updates */
    private ?bool $destructiveHint;

    /** @var null|bool If true, calling the tool repeatedly has no additional effect */
    private ?bool $idempotentHint;

    /** @var null|bool If true, this tool may interact with an "open world" */
    private ?bool $openWorldHint;

    public function __construct(
        ?string $title = null,
        ?bool $readOnlyHint = null,
        ?bool $destructiveHint = null,
        ?bool $idempotentHint = null,
        ?bool $openWorldHint = null
    ) {
        $this->setTitle($title);
        $this->readOnlyHint = $readOnlyHint;
        $this->destructiveHint = $destructiveHint;
        $this->idempotentHint = $idempotentHint;
        $this->openWorldHint = $openWorldHint;
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $title = null;
        if (isset($data['title'])) {
            if (! is_string($data['title'])) {
                throw ValidationError::invalidFieldType('title', 'string', gettype($data['title']));
            }
            $title = $data['title'];
        }

        $readOnlyHint = null;
        if (isset($data['readOnlyHint'])) {
            if (! is_bool($data['readOnlyHint'])) {
                throw ValidationError::invalidFieldType('readOnlyHint', 'boolean', gettype($data['readOnlyHint']));
            }
            $readOnlyHint = $data['readOnlyHint'];
        }

        $destructiveHint = null;
        if (isset($data['destructiveHint'])) {
            if (! is_bool($data['destructiveHint'])) {
                throw ValidationError::invalidFieldType('destructiveHint', 'boolean', gettype($data['destructiveHint']));
            }
            $destructiveHint = $data['destructiveHint'];
        }

        $idempotentHint = null;
        if (isset($data['idempotentHint'])) {
            if (! is_bool($data['idempotentHint'])) {
                throw ValidationError::invalidFieldType('idempotentHint', 'boolean', gettype($data['idempotentHint']));
            }
            $idempotentHint = $data['idempotentHint'];
        }

        $openWorldHint = null;
        if (isset($data['openWorldHint'])) {
            if (! is_bool($data['openWorldHint'])) {
                throw ValidationError::invalidFieldType('openWorldHint', 'boolean', gettype($data['openWorldHint']));
            }
            $openWorldHint = $data['openWorldHint'];
        }

        return new self(
            $title,
            $readOnlyHint,
            $destructiveHint,
            $idempotentHint,
            $openWorldHint
        );
    }

    /**
     * Get the human-readable title for the tool.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set the human-readable title for the tool.
     */
    public function setTitle(?string $title): void
    {
        if ($title !== null) {
            $title = trim($title);
            if (empty($title)) {
                $title = null;
            }
        }
        $this->title = $title;
    }

    /**
     * Get the read-only hint.
     */
    public function getReadOnlyHint(): ?bool
    {
        return $this->readOnlyHint;
    }

    /**
     * Set the read-only hint.
     */
    public function setReadOnlyHint(?bool $readOnlyHint): void
    {
        $this->readOnlyHint = $readOnlyHint;
    }

    /**
     * Get the destructive hint.
     */
    public function getDestructiveHint(): ?bool
    {
        return $this->destructiveHint;
    }

    /**
     * Set the destructive hint.
     */
    public function setDestructiveHint(?bool $destructiveHint): void
    {
        $this->destructiveHint = $destructiveHint;
    }

    /**
     * Get the idempotent hint.
     */
    public function getIdempotentHint(): ?bool
    {
        return $this->idempotentHint;
    }

    /**
     * Set the idempotent hint.
     */
    public function setIdempotentHint(?bool $idempotentHint): void
    {
        $this->idempotentHint = $idempotentHint;
    }

    /**
     * Get the open world hint.
     */
    public function getOpenWorldHint(): ?bool
    {
        return $this->openWorldHint;
    }

    /**
     * Set the open world hint.
     */
    public function setOpenWorldHint(?bool $openWorldHint): void
    {
        $this->openWorldHint = $openWorldHint;
    }

    /**
     * Check if annotations have a title.
     */
    public function hasTitle(): bool
    {
        return $this->title !== null;
    }

    /**
     * Check if annotations have a read-only hint.
     */
    public function hasReadOnlyHint(): bool
    {
        return $this->readOnlyHint !== null;
    }

    /**
     * Check if annotations have a destructive hint.
     */
    public function hasDestructiveHint(): bool
    {
        return $this->destructiveHint !== null;
    }

    /**
     * Check if annotations have an idempotent hint.
     */
    public function hasIdempotentHint(): bool
    {
        return $this->idempotentHint !== null;
    }

    /**
     * Check if annotations have an open world hint.
     */
    public function hasOpenWorldHint(): bool
    {
        return $this->openWorldHint !== null;
    }

    /**
     * Check if the tool is read-only (default: false).
     */
    public function isReadOnly(): bool
    {
        return $this->readOnlyHint === true;
    }

    /**
     * Check if the tool is destructive (default: true when not read-only).
     */
    public function isDestructive(): bool
    {
        if ($this->isReadOnly()) {
            return false;
        }
        return $this->destructiveHint !== false;
    }

    /**
     * Check if the tool is idempotent (default: false when not read-only).
     */
    public function isIdempotent(): bool
    {
        if ($this->isReadOnly()) {
            return true;
        }
        return $this->idempotentHint === true;
    }

    /**
     * Check if the tool operates in an open world (default: true).
     */
    public function isOpenWorld(): bool
    {
        return $this->openWorldHint !== false;
    }

    /**
     * Check if annotations are empty.
     */
    public function isEmpty(): bool
    {
        return ! $this->hasTitle()
               && ! $this->hasReadOnlyHint()
               && ! $this->hasDestructiveHint()
               && ! $this->hasIdempotentHint()
               && ! $this->hasOpenWorldHint();
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->readOnlyHint !== null) {
            $data['readOnlyHint'] = $this->readOnlyHint;
        }

        if ($this->destructiveHint !== null) {
            $data['destructiveHint'] = $this->destructiveHint;
        }

        if ($this->idempotentHint !== null) {
            $data['idempotentHint'] = $this->idempotentHint;
        }

        if ($this->openWorldHint !== null) {
            $data['openWorldHint'] = $this->openWorldHint;
        }

        return $data;
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create a copy with different title.
     */
    public function withTitle(?string $title): self
    {
        return new self(
            $title,
            $this->readOnlyHint,
            $this->destructiveHint,
            $this->idempotentHint,
            $this->openWorldHint
        );
    }

    /**
     * Create a copy with different read-only hint.
     */
    public function withReadOnlyHint(?bool $readOnlyHint): self
    {
        return new self(
            $this->title,
            $readOnlyHint,
            $this->destructiveHint,
            $this->idempotentHint,
            $this->openWorldHint
        );
    }

    /**
     * Create a copy with different destructive hint.
     */
    public function withDestructiveHint(?bool $destructiveHint): self
    {
        return new self(
            $this->title,
            $this->readOnlyHint,
            $destructiveHint,
            $this->idempotentHint,
            $this->openWorldHint
        );
    }

    /**
     * Create a copy with different idempotent hint.
     */
    public function withIdempotentHint(?bool $idempotentHint): self
    {
        return new self(
            $this->title,
            $this->readOnlyHint,
            $this->destructiveHint,
            $idempotentHint,
            $this->openWorldHint
        );
    }

    /**
     * Create a copy with different open world hint.
     */
    public function withOpenWorldHint(?bool $openWorldHint): self
    {
        return new self(
            $this->title,
            $this->readOnlyHint,
            $this->destructiveHint,
            $this->idempotentHint,
            $openWorldHint
        );
    }
}
