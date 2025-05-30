<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Types\Responses;

use Dtyq\PhpMcp\Types\Core\ResultInterface;

/**
 * Simple result that returns an empty object for ping responses.
 *
 * According to MCP specification, ping responses should contain an empty object.
 */
class PingResult implements ResultInterface
{
    /** @var null|array<string, mixed> */
    private ?array $meta = null;

    public function toArray(): array
    {
        return [];
    }

    public function hasMeta(): bool
    {
        return $this->meta !== null;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    public function isPaginated(): bool
    {
        return false;
    }

    public function getNextCursor(): ?string
    {
        return null;
    }
}
