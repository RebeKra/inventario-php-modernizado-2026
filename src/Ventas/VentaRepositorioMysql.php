<?php

namespace App\Ventas;

use App\Database\Conexion;

final class VentaRepositorioMysql implements VentaRepositorio
{
    public function __construct(private readonly Conexion $db)
    {
    }

    public function registrar(int $productoId, int $cantidad, string $total, string $fecha): int
    {
        $this->db->ejecutar(
            'INSERT INTO sales (product_id, qty, price, date) VALUES (?, ?, ?, ?)',
            [$productoId, $cantidad, $total, $fecha]
        );
        return $this->db->ultimoId();
    }
}
