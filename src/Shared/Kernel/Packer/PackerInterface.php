<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Kernel\Packer;

interface PackerInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function pack(array $data): string;

    /**
     * @return array<string, mixed>
     */
    public function unpack(string $data): array;
}
