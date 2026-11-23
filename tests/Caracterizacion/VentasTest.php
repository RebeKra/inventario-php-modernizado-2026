<?php

namespace Tests\Caracterizacion;

use Tests\Support\PruebaDeCaracterizacion;
use Tests\Support\TestDatabase;

/**
 * Caracteriza el registro de ventas y su efecto sobre el stock.
 */
final class VentasTest extends PruebaDeCaracterizacion
{
    private function registrarVenta(int $productoId, int $cantidad, string $total): void
    {
        $this->navegador->post('/add_sale.php', [
            's_id' => $productoId,
            'quantity' => $cantidad,
            'price' => '0',
            'total' => $total,
            'date' => '2025-10-05',
            'add_sale' => '',
        ]);
    }

    public function testUnaVentaSeRegistraYDescuentaElStock(): void
    {
        $this->comoAdministrador();

        $this->registrarVenta(1, 5, '225000');

        $this->assertSame(25, $this->stockDelProducto(1));
        $venta = TestDatabase::fetchOne('SELECT * FROM sales WHERE product_id = 1');
        $this->assertSame('5', $venta['qty']);
        $this->assertSame('225000.00', $venta['price']);
    }

    /**
     * COMPORTAMIENTO ACTUAL – hallazgo H-04 (ver AUDITORIA.md).
     * Se puede vender más de lo que hay en stock y el stock queda negativo.
     */
    public function testUnaVentaMayorAlStockDejaElStockNegativo(): void
    {
        $this->comoAdministrador();

        $this->registrarVenta(2, 10, '8900000');

        $this->assertSame(-6, $this->stockDelProducto(2));
    }

    /**
     * COMPORTAMIENTO ACTUAL – hallazgo H-05 (ver AUDITORIA.md).
     * El total de la venta lo envía el navegador y el servidor lo guarda sin recalcularlo:
     * se pueden vender 3 baterías por Gs. 1.
     */
    public function testElTotalDeLaVentaLoDecideElNavegador(): void
    {
        $this->comoAdministrador();

        $this->registrarVenta(2, 3, '1');

        $venta = TestDatabase::fetchOne('SELECT price FROM sales WHERE product_id = 2');
        $this->assertSame('1.00', $venta['price']);
    }
}
