<?php

namespace App\Auth;

use App\Security\Contrasenas;

/**
 * Inicio de sesión con migración transparente de contraseñas (corrige H-01).
 */
final class Autenticacion
{
    public function __construct(
        private readonly UsuarioRepositorio $usuarios,
        private readonly Contrasenas $contrasenas,
    ) {
    }

    /** @return array{id: int, username: string, password: string, user_level: int}|null */
    public function autenticar(string $usuario, string $contrasena): ?array
    {
        $registro = $this->usuarios->buscarPorNombreDeUsuario($usuario);
        if (!$registro) {
            return null;
        }

        $resultado = $this->contrasenas->verificar($contrasena, $registro['password']);
        if (!$resultado->valida) {
            return null;
        }

        if ($resultado->requiereActualizar) {
            $this->usuarios->actualizarContrasena($registro['id'], $this->contrasenas->hash($contrasena));
        }

        return $registro;
    }
}
