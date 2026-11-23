<?php

namespace Tests\Caracterizacion;

use Tests\Support\PruebaDeCaracterizacion;
use Tests\Support\TestDatabase;

/**
 * Caracteriza el inicio de sesión y el control de acceso del sistema original.
 */
final class AutenticacionTest extends PruebaDeCaracterizacion
{
    public function testConCredencialesCorrectasRedirigeAlInicio(): void
    {
        $respuesta = $this->navegador->iniciarSesion('admin', 'admin');

        $this->assertSame(302, $respuesta->status);
        $this->assertSame('home.php', $respuesta->location);
    }

    public function testConClaveIncorrectaVuelveAlFormularioDeLogin(): void
    {
        $respuesta = $this->navegador->iniciarSesion('admin', 'clave-equivocada');

        $this->assertSame('index.php', $respuesta->location);
        $this->assertSame('index.php', $this->navegador->get('/product.php')->location);
    }

    public function testUnaPaginaProtegidaSinSesionRedirigeAlLogin(): void
    {
        $respuesta = $this->navegador->get('/product.php');

        $this->assertSame('index.php', $respuesta->location);
    }

    public function testUnUsuarioDeNivel3NoPuedeAgregarProductos(): void
    {
        $this->navegador->iniciarSesion('user', 'user');

        $respuesta = $this->navegador->get('/add_product.php');

        $this->assertSame('home.php', $respuesta->location);
    }

    /**
     * COMPORTAMIENTO ACTUAL – hallazgo H-01 (ver AUDITORIA.md).
     * Las contraseñas se guardan como SHA-1 sin sal. Esta prueba cambiará en la Fase 2.
     */
    public function testLasContrasenasSeGuardanComoSha1SinSal(): void
    {
        $usuario = TestDatabase::fetchOne("SELECT password FROM users WHERE username = 'admin'");

        $this->assertSame(sha1('admin'), $usuario['password']);
    }
}
