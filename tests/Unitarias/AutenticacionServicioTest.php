<?php

namespace Tests\Unitarias;

use App\Auth\Autenticacion;
use App\Auth\UsuarioRepositorio;
use App\Security\Contrasenas;
use PHPUnit\Framework\TestCase;

final class AutenticacionServicioTest extends TestCase
{
    private UsuariosEnMemoria $usuarios;
    private Autenticacion $autenticacion;

    protected function setUp(): void
    {
        $this->usuarios = new UsuariosEnMemoria([
            'admin' => ['id' => 1, 'username' => 'admin', 'password' => sha1('admin'), 'user_level' => 1],
            'ana' => ['id' => 2, 'username' => 'ana', 'password' => password_hash('secreta', PASSWORD_DEFAULT), 'user_level' => 3],
        ]);
        $this->autenticacion = new Autenticacion($this->usuarios, new Contrasenas());
    }

    public function testUnaContrasenaHeredadaCorrectaIniciaSesionYSeMigra(): void
    {
        $usuario = $this->autenticacion->autenticar('admin', 'admin');

        $this->assertSame(1, $usuario['id']);
        $this->assertTrue(password_verify('admin', $this->usuarios->hash('admin')));
    }

    public function testUnaContrasenaHeredadaIncorrectaNoSeMigra(): void
    {
        $this->assertNull($this->autenticacion->autenticar('admin', 'otra'));
        $this->assertSame(sha1('admin'), $this->usuarios->hash('admin'));
    }

    public function testUnaContrasenaBcryptCorrectaNoSeVuelveAGuardar(): void
    {
        $hashOriginal = $this->usuarios->hash('ana');

        $this->assertNotNull($this->autenticacion->autenticar('ana', 'secreta'));
        $this->assertSame($hashOriginal, $this->usuarios->hash('ana'));
    }

    public function testUnUsuarioInexistenteNoIniciaSesion(): void
    {
        $this->assertNull($this->autenticacion->autenticar('nadie', 'admin'));
    }
}

final class UsuariosEnMemoria implements UsuarioRepositorio
{
    /** @param array<string, array{id: int, username: string, password: string, user_level: int}> $usuarios */
    public function __construct(private array $usuarios)
    {
    }

    public function buscarPorNombreDeUsuario(string $usuario): ?array
    {
        return $this->usuarios[$usuario] ?? null;
    }

    public function actualizarContrasena(int $id, string $hash): void
    {
        foreach ($this->usuarios as $nombre => $datos) {
            if ($datos['id'] === $id) {
                $this->usuarios[$nombre]['password'] = $hash;
            }
        }
    }

    public function hash(string $usuario): string
    {
        return $this->usuarios[$usuario]['password'];
    }
}
