<?php

namespace Tests\Support;

final class Respuesta
{
    public function __construct(
        public readonly int $status,
        public readonly ?string $location,
        public readonly string $body,
    ) {
    }
}
