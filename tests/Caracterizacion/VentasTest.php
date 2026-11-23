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

    /** Hallazgo H-04 corregido: no se puede vender más de lo que hay en stock. */
    public function testUnaVentaMayorAlStockSeRechazaYNoCambiaNada(): void
    {
        $this->comoAdministrador();

        $this->registrarVenta(2, 10, '8900000');

        $this->assertSame(4, $this->stockDelProducto(2));
        $this->assertNull(TestDatabase::fetchOne('SELECT id FROM sales WHERE product_id = 2'));
        $this->assertStringContainsString('Stock insuficiente', $this->navegador->get('/add_sale.php')->body);
    }

    /** Hallazgo H-05 corregido: el total lo calcula el servidor con el precio guardado. */
    public function testElTotalLoCalculaElServidorAunqueElNavegadorEnvieOtro(): void
    {
        $this->comoAdministrador();

        $this->registrarVenta(2, 3, '1');

        $venta = TestDatabase::fetchOne('SELECT price FROM sales WHERE product_id = 2');
        $this->assertSame('2670000.00', $venta['price']);
    }
}
