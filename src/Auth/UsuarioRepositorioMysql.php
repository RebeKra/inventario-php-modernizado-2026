<?php

namespace App\Auth;

use App\Database\Conexion;

final class UsuarioRepositorioMysql implements UsuarioRepositorio
{
    public function __construct(private readonly Conexion $db)
    {
    }

    public function buscarPorNombreDeUsuario(string $usuario): ?array
    {
        $fila = $this->db->uno(
            'SELECT id, username, password, user_level FROM users WHERE username = ? LIMIT 1',
            [$usuario]
        );
        if (!$fila) {
            return null;
        }
        $fila['id'] = (int) $fila['id'];
        $fila['user_level'] = (int) $fila['user_level'];
        return $fila;
    }

    public function actualizarContrasena(int $id, string $hash): void
    {
        $this->db->ejecutar('UPDATE users SET password = ? WHERE id = ?', [$hash, $id]);
    }
}
