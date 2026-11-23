<?php

namespace Tests\Support;

/**
 * Crea desde cero la base de datos de pruebas con el esquema original
 * (oswa_inv.sql) y los datos de prueba (tests/fixtures.sql).
 */
final class TestDatabase
{
    public static function config(string $key): string
    {
        return (string) getenv('TEST_DB_' . $key);
    }

    public static function connect(bool $selectDb = true): \mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        return new \mysqli(
            self::config('HOST'),
            self::config('USER'),
            self::config('PASS'),
            $selectDb ? self::config('NAME') : null
        );
    }

    public static function reset(): void
    {
        $name = self::config('NAME');
        $db = self::connect(false);
        // El sistema original se escribió para el modo no estricto de MySQL 5.
        $db->query("SET GLOBAL sql_mode = ''");
        $db->query("DROP DATABASE IF EXISTS `$name`");
        $db->query("CREATE DATABASE `$name` CHARACTER SET utf8");
        $db->select_db($name);

        $archivos = array_merge(
            [__DIR__ . '/../../oswa_inv.sql'],
            glob(__DIR__ . '/../../database/migraciones/*.sql'),
            [__DIR__ . '/../fixtures.sql']
        );
        foreach ($archivos as $file) {
            $db->multi_query(file_get_contents($file));
            while ($db->more_results() && $db->next_result()) {
                // Consumir todos los resultados de la carga.
            }
        }
        $db->close();
    }

    /** @return array<string, mixed>|null */
    public static function fetchOne(string $sql): ?array
    {
        $db = self::connect();
        $row = $db->query($sql)->fetch_assoc();
        $db->close();
        return $row;
    }
}
