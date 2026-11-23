<?php

namespace App\Database;

interface Transacciones
{
    /**
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    public function transaccion(callable $operacion): mixed;
}
