<?php

namespace App\Ventas;

use App\Database\Conexion;

final class ProductoRepositorioMysql implements ProductoRepositorio
{
    public function __construct(private readonly Conexion $db)
    {
    }

    public function buscar(int $id): ?array
    {
        $fila = $this->db->uno('SELECT id, name, sale_price, quantity FROM products WHERE id = ?', [$id]);
        if (!$fila) {
            return null;
        }
        $fila['id'] = (int) $fila['id'];
        $fila['quantity'] = (int) $fila['quantity'];
        return $fila;
    }

    public function descontarStock(int $id, int $cantidad): bool
    {
        // La condición en el WHERE hace la verificación y el descuento en una sola operación
        // atómica: dos ventas simultáneas no pueden dejar el stock negativo (corrige H-04).
        return $this->db->filasAfectadas(
            'UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?',
            [$cantidad, $id, $cantidad]
        ) === 1;
    }
}
