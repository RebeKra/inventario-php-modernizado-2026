<?php

namespace App\Security;

/**
 * Hash de contraseñas con bcrypt (corrige H-01).
 *
 * Acepta los hashes SHA-1 heredados para no obligar a nadie a restablecer su
 * contraseña: si una contraseña heredada es correcta, el resultado indica que
 * hay que volver a guardarla con el algoritmo nuevo.
 */
final class Contrasenas
{
    public function hash(string $contrasena): string
    {
        return password_hash($contrasena, PASSWORD_DEFAULT);
    }

    public function verificar(string $contrasena, string $hashGuardado): ResultadoVerificacion
    {
        if ($this->esHashHeredado($hashGuardado)) {
            $valida = hash_equals($hashGuardado, sha1($contrasena));
            return new ResultadoVerificacion($valida, $valida);
        }

        $valida = password_verify($contrasena, $hashGuardado);
        return new ResultadoVerificacion($valida, $valida && password_needs_rehash($hashGuardado, PASSWORD_DEFAULT));
    }

    public function esHashHeredado(string $hash): bool
    {
        return (bool) preg_match('/^[a-f0-9]{40}$/', $hash);
    }
}
