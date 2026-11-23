<?php

namespace App\Ventas;

interface ProductoRepositorio
{
    /** @return array{id: int, name: string, sale_price: string, quantity: int}|null */
    public function buscar(int $id): ?array;

    /**
     * Descuenta stock solo si alcanza. Devuelve false si no había stock suficiente.
     */
    public function descontarStock(int $id, int $cantidad): bool;
}
