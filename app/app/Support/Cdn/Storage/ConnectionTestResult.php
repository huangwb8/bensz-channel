<?php

namespace App\Support\Cdn\Storage;

class ConnectionTestResult
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly bool $successful,
        public readonly string $message,
        public readonly string $details = '',
        public readonly array $context = [],
    ) {}
}
