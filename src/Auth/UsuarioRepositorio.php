<?php

namespace App\Auth;

interface UsuarioRepositorio
{
    /** @return array{id: int, username: string, password: string, user_level: int}|null */
    public function buscarPorNombreDeUsuario(string $usuario): ?array;

    public function actualizarContrasena(int $id, string $hash): void;
}
