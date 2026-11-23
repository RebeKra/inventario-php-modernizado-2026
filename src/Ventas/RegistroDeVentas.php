<?php

namespace App\Ventas;

use App\Database\Transacciones;

/**
 * Reglas de negocio de una venta, separadas de la página que la muestra.
 *
 * - El total se calcula en el servidor con el precio guardado (corrige H-05).
 * - No se puede vender más de lo que hay en stock (corrige H-04).
 * - La venta y el descuento de stock son una sola transacción (corrige H-08).
 */
final class RegistroDeVentas
{
    public function __construct(
        private readonly ProductoRepositorio $productos,
        private readonly VentaRepositorio $ventas,
        private readonly Transacciones $transacciones,
    ) {
    }

    public function registrar(int $productoId, int $cantidad, \DateTimeImmutable $fecha): Venta
    {
        if ($cantidad <= 0) {
            throw new VentaInvalida('La cantidad debe ser mayor que cero.');
        }

        return $this->transacciones->transaccion(function () use ($productoId, $cantidad, $fecha): Venta {
            $producto = $this->productos->buscar($productoId);
            if (!$producto) {
                throw new VentaInvalida('El producto no existe.');
            }

            if (!$this->productos->descontarStock($productoId, $cantidad)) {
                throw new StockInsuficiente(sprintf(
                    'Stock insuficiente para "%s": hay %d y se intentó vender %d.',
                    $producto['name'],
                    $producto['quantity'],
                    $cantidad
                ));
            }

            $total = self::multiplicar((string) $producto['sale_price'], $cantidad);
            $id = $this->ventas->registrar($productoId, $cantidad, $total, $fecha->format('Y-m-d H:i:s'));

            return new Venta($id, $productoId, $cantidad, $total);
        });
    }

    /**
     * Multiplica un precio DECIMAL(25,2) por una cantidad usando céntimos enteros,
     * para no arrastrar errores de redondeo de los números de coma flotante.
     */
    public static function multiplicar(string $precio, int $cantidad): string
    {
        [$entero, $decimales] = array_pad(explode('.', $precio, 2), 2, '0');
        $centimos = ((int) $entero * 100 + (int) str_pad(substr($decimales, 0, 2), 2, '0')) * $cantidad;

        return intdiv($centimos, 100) . '.' . str_pad((string) ($centimos % 100), 2, '0', STR_PAD_LEFT);
    }
}
