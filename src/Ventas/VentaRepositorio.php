<?php

namespace App\Ventas;

interface VentaRepositorio
{
    public function registrar(int $productoId, int $cantidad, string $total, string $fecha): int;
}
