<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Kernel\Packer;

class OpisClosurePacker implements PackerInterface
{
    /**
     * Pack data into a serialized string.
     *
     * @param array<string, mixed> $data
     */
    public function pack(array $data): string
    {
        return \Opis\Closure\serialize($data);
    }

    /**
     * Unpack a serialized string into an array.
     *
     * @return array<string, mixed>
     */
    public function unpack(string $data): array
    {
        $unpacked = \Opis\Closure\unserialize($data);
        if (! is_array($unpacked)) {
            return [];
        }
        return $unpacked;
    }
}
