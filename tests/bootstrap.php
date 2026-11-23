<?php
/**
 * Arranque de las pruebas de caracterización.
 *
 * Las pruebas tratan al sistema como una caja negra: levantan el sitio original
 * con el servidor embebido de PHP, apuntando a una base de datos de pruebas, y
 * hacen peticiones HTTP como lo haría un navegador. Así no hace falta modificar
 * el código legado para poder probarlo.
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
} else {
    // Permite correr las pruebas con un PHPUnit instalado globalmente.
    spl_autoload_register(function (string $class): void {
        if (str_starts_with($class, 'Tests\\')) {
            require __DIR__ . '/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        }
    });
}

Tests\Support\TestServer::start();
register_shutdown_function([Tests\Support\TestServer::class, 'stop']);
