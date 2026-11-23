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
     * Hallazgo H-01 corregido: al iniciar sesión con una contraseña heredada (SHA-1),
     * se vuelve a guardar con bcrypt sin que el usuario tenga que hacer nada.
     */
    public function testAlIniciarSesionLaContrasenaHeredadaSeMigraABcrypt(): void
    {
        $this->assertSame(sha1('admin'), $this->hashDe('admin'));

        $this->navegador->iniciarSesion('admin', 'admin');

        $hash = $this->hashDe('admin');
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(password_verify('admin', $hash));
    }

    public function testDespuesDeMigrarSePuedeSeguirIniciandoSesion(): void
    {
        $this->navegador->iniciarSesion('admin', 'admin');
        $otroNavegador = new \Tests\Support\Navegador();

        $respuesta = $otroNavegador->iniciarSesion('admin', 'admin');

        $this->assertSame('home.php', $respuesta->location);
    }

    /** Hallazgo H-06 corregido: el identificador de sesión cambia al iniciar sesión. */
    public function testElIdentificadorDeSesionCambiaAlIniciarSesion(): void
    {
        $this->navegador->get('/index.php');
        $antes = $this->navegador->idDeSesion();

        $this->navegador->iniciarSesion('admin', 'admin');

        $this->assertNotNull($antes);
        $this->assertNotSame($antes, $this->navegador->idDeSesion());
    }

    /**
     * Hallazgo H-11 corregido: el formulario enviaba el id del usuario y se podía
     * cambiar la contraseña de otra persona modificándolo.
     */
    public function testNoSePuedeCambiarLaContrasenaDeOtroUsuario(): void
    {
        $this->navegador->iniciarSesion('user', 'user');
        $hashDelAdmin = $this->hashDe('admin');

        $this->navegador->post('/change_password.php', [
            'old-password' => 'user',
            'new-password' => 'nueva-clave',
            'id' => 1,
            'update' => '',
        ]);

        $this->assertSame($hashDelAdmin, $this->hashDe('admin'));
        $this->assertTrue(password_verify('nueva-clave', $this->hashDe('user')));
    }

    private function hashDe(string $usuario): string
    {
        return TestDatabase::fetchOne("SELECT password FROM users WHERE username = '$usuario'")['password'];
    }
}
