<?php

namespace App\Ventas;

final class Venta
{
    public function __construct(
        public readonly int $id,
        public readonly int $productoId,
        public readonly int $cantidad,
        public readonly string $total,
    ) {
    }
}
