<?php

namespace App\Security;

/**
 * Tokens CSRF por sesión (corrige H-03).
 */
final class Csrf
{
    public const CAMPO = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::CAMPO])) {
            $_SESSION[self::CAMPO] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CAMPO];
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="' . self::CAMPO . '" value="' . self::token() . '">';
    }

    public static function esValido(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION[self::CAMPO])
            && hash_equals($_SESSION[self::CAMPO], $token);
    }

    /**
     * Publica el token en la cookie XSRF-TOKEN para que la interfaz en React pueda
     * leerlo y enviarlo en la cabecera X-CSRF-Token. La cookie no da acceso por sí
     * sola: otro sitio no puede leerla, y sin la cabecera la petición se rechaza.
     */
    public static function publicarCookie(): void
    {
        if (headers_sent() || ($_COOKIE['XSRF-TOKEN'] ?? null) === self::token()) {
            return;
        }
        setcookie('XSRF-TOKEN', self::token(), [
            'path' => '/',
            'samesite' => 'Strict',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => false,
        ]);
    }

    /**
     * Rechaza cualquier petición POST sin un token válido.
     * El token puede llegar en el formulario o en la cabecera X-CSRF-Token (peticiones AJAX).
     */
    public static function protegerPeticion(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }
        $token = $_POST[self::CAMPO] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!self::esValido($token)) {
            http_response_code(403);
            exit('Solicitud no válida: el formulario expiró o no proviene de este sitio. Vuelve atrás y recarga la página.');
        }
    }
}
