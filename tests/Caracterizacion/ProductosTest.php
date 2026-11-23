<?php

namespace Tests\Caracterizacion;

use Tests\Support\PruebaDeCaracterizacion;
use Tests\Support\TestDatabase;

/**
 * Caracteriza el alta, el listado y la baja de productos.
 */
final class ProductosTest extends PruebaDeCaracterizacion
{
    public function testElListadoMuestraLosProductos(): void
    {
        $this->comoAdministrador();

        $respuesta = $this->navegador->get('/product.php');

        $this->assertSame(200, $respuesta->status);
        $this->assertStringContainsString('Filtro de aceite', $respuesta->body);
        $this->assertStringContainsString('Batería 60Ah', $respuesta->body);
    }

    public function testAgregarUnProductoLoGuardaYApareceEnElListado(): void
    {
        $this->comoAdministrador();

        $respuesta = $this->navegador->post('/add_product.php', [
            'product-title' => 'Bujía de iridio',
            'product-categorie' => 1,
            'product-quantity' => 40,
            'buying-price' => 38000,
            'saleing-price' => 55000,
            'product-photo' => '',
            'add_product' => '',
        ]);

        $this->assertSame('add_product.php', $respuesta->location);
        $producto = TestDatabase::fetchOne("SELECT * FROM products WHERE name = 'Bujía de iridio'");
        $this->assertNotNull($producto);
        $this->assertSame('40', $producto['quantity']);
        $this->assertStringContainsString('Bujía de iridio', $this->navegador->get('/product.php')->body);
    }

    public function testAgregarUnProductoSinNombreNoLoGuarda(): void
    {
        $this->comoAdministrador();

        $this->navegador->post('/add_product.php', [
            'product-title' => '',
            'product-categorie' => 1,
            'product-quantity' => 5,
            'buying-price' => 1000,
            'saleing-price' => 2000,
            'product-photo' => '',
            'add_product' => '',
        ]);

        $this->assertSame(2, (int) TestDatabase::fetchOne('SELECT COUNT(*) AS n FROM products')['n']);
    }

    /** Hallazgo H-03 corregido: un enlace GET ya no elimina nada. */
    public function testUnEnlaceGetYaNoEliminaUnProducto(): void
    {
        $this->comoAdministrador();

        $this->navegador->get('/delete_product.php?id=2');

        $this->assertNotNull(TestDatabase::fetchOne('SELECT id FROM products WHERE id = 2'));
    }

    /** Hallazgo H-03 corregido: un formulario sin token CSRF (por ejemplo, desde otro sitio) se rechaza. */
    public function testUnPostSinTokenCsrfSeRechaza(): void
    {
        $this->comoAdministrador();

        $respuesta = $this->navegador->postSinToken('/delete_product.php', ['id' => 2]);

        $this->assertSame(403, $respuesta->status);
        $this->assertNotNull(TestDatabase::fetchOne('SELECT id FROM products WHERE id = 2'));
    }

    public function testElBotonEliminarConTokenCsrfEliminaElProducto(): void
    {
        $this->comoAdministrador();

        $this->navegador->post('/delete_product.php', ['id' => 2], '/product.php');

        $this->assertNull(TestDatabase::fetchOne('SELECT id FROM products WHERE id = 2'));
    }
}
