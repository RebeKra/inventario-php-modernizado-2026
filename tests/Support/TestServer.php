<?php

namespace Tests\Support;

/**
 * Levanta el sistema original con el servidor embebido de PHP,
 * configurado para usar la base de datos de pruebas.
 */
final class TestServer
{
    /** @var resource|null */
    private static $process = null;

    public static function baseUrl(): string
    {
        return 'http://127.0.0.1:' . getenv('TEST_PORT');
    }

    public static function start(): void
    {
        $env = array_merge(getenv(), [
            'DB_HOST' => TestDatabase::config('HOST'),
            'DB_USER' => TestDatabase::config('USER'),
            'DB_PASS' => TestDatabase::config('PASS'),
            'DB_NAME' => TestDatabase::config('NAME'),
        ]);
        $root = realpath(__DIR__ . '/../..');
        // Sin búfer de salida, como en la imagen oficial de PHP para Docker: así las pruebas
        // detectan cualquier texto que se envíe antes de los encabezados (H-16).
        $cmd = [PHP_BINARY, '-d', 'output_buffering=0', '-S', '127.0.0.1:' . getenv('TEST_PORT'), '-t', $root];

        self::$process = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, $root, $env);

        for ($i = 0; $i < 50; $i++) {
            if (@fsockopen('127.0.0.1', (int) getenv('TEST_PORT'))) {
                return;
            }
            usleep(100000);
        }
        throw new \RuntimeException('No se pudo iniciar el servidor de pruebas');
    }

    public static function stop(): void
    {
        if (self::$process) {
            proc_terminate(self::$process);
            self::$process = null;
        }
    }
}
