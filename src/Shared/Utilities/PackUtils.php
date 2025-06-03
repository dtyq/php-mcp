<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Utilities;

class PackUtils
{
    /**
     * @param array<string, mixed> $data
     */
    public static function pack(array $data): string
    {
        return \Opis\Closure\serialize($data);
    }

    /**
     * @return array<string, mixed>
     */
    public static function unpack(string $data): array
    {
        $unpacked = \Opis\Closure\unserialize($data);
        if (! is_array($unpacked)) {
            return [];
        }
        return $unpacked;
    }
}
