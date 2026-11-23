<?php

namespace Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * Base de las pruebas de caracterización: cada prueba arranca
 * con la base de datos recién creada y un navegador nuevo.
 */
abstract class PruebaDeCaracterizacion extends TestCase
{
    protected Navegador $navegador;

    protected function setUp(): void
    {
        TestDatabase::reset();
        $this->navegador = new Navegador();
    }

    protected function comoAdministrador(): void
    {
        $this->navegador->iniciarSesion('admin', 'admin');
    }

    protected function stockDelProducto(int $id): int
    {
        return (int) TestDatabase::fetchOne("SELECT quantity FROM products WHERE id = $id")['quantity'];
    }
}
