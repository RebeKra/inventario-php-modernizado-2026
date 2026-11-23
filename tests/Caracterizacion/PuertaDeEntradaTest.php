<?php

namespace Tests\Caracterizacion;

use Tests\Support\PruebaDeCaracterizacion;

/**
 * Verificación de sesión que usa nginx (auth_request) para proteger la API
 * y la interfaz nueva con el inicio de sesión del sistema PHP.
 */
final class PuertaDeEntradaTest extends PruebaDeCaracterizacion
{
    public function testSinSesionResponde401(): void
    {
        $this->assertSame(401, $this->navegador->get('/api_sesion.php')->status);
    }

    public function testConSesionDeAdministradorPermiteLeer(): void
    {
        $this->comoAdministrador();

        $this->assertSame(204, $this->navegador->get('/api_sesion.php', ['X-Original-Method' => 'GET'])->status);
    }

    public function testUnUsuarioSinPermisoSobreProductosRecibe403(): void
    {
        $this->navegador->iniciarSesion('user', 'user');

        $this->assertSame(403, $this->navegador->get('/api_sesion.php')->status);
    }

    public function testUnaModificacionSinTokenCsrfRecibe403(): void
    {
        $this->comoAdministrador();

        $respuesta = $this->navegador->get('/api_sesion.php', ['X-Original-Method' => 'POST']);

        $this->assertSame(403, $respuesta->status);
    }

    public function testUnaModificacionConElTokenDeLaCookieEsAceptada(): void
    {
        $this->comoAdministrador();
        $this->navegador->get('/home.php');
        $token = $this->navegador->cookie('XSRF-TOKEN');

        $respuesta = $this->navegador->get('/api_sesion.php', [
            'X-Original-Method' => 'DELETE',
            'X-CSRF-Token' => (string) $token,
        ]);

        $this->assertNotNull($token);
        $this->assertSame(204, $respuesta->status);
    }

    public function testUnTokenInventadoEsRechazado(): void
    {
        $this->comoAdministrador();

        $respuesta = $this->navegador->get('/api_sesion.php', [
            'X-Original-Method' => 'PUT',
            'X-CSRF-Token' => str_repeat('a', 64),
        ]);

        $this->assertSame(403, $respuesta->status);
    }
}
