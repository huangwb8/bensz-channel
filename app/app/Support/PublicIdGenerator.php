<?php

namespace App\Support;

class PublicIdGenerator
{
    private const BYTE_LENGTH = 8;

    public static function make(): string
    {
        return bin2hex(random_bytes(self::BYTE_LENGTH));
    }
}
