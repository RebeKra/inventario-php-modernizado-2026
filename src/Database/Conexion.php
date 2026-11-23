<?php

namespace App\Database;

/**
 * Acceso a MySQL con consultas preparadas (corrige H-02 en el código nuevo).
 *
 * Reutiliza la conexión mysqli del sistema original para no abrir dos conexiones
 * por petición mientras ambos estilos de código conviven.
 */
final class Conexion implements Transacciones
{
    public function __construct(private readonly \mysqli $mysqli)
    {
    }

    /** @param list<scalar|null> $params */
    public function ejecutar(string $sql, array $params = []): \mysqli_stmt
    {
        $stmt = $this->mysqli->prepare($sql);
        if ($params) {
            $tipos = '';
            foreach ($params as $p) {
                $tipos .= match (true) {
                    is_int($p) => 'i',
                    is_float($p) => 'd',
                    default => 's',
                };
            }
            $stmt->bind_param($tipos, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    /**
     * @param list<scalar|null> $params
     * @return array<string, mixed>|null
     */
    public function uno(string $sql, array $params = []): ?array
    {
        $resultado = $this->ejecutar($sql, $params)->get_result();
        return $resultado ? ($resultado->fetch_assoc() ?: null) : null;
    }

    /** @param list<scalar|null> $params */
    public function filasAfectadas(string $sql, array $params = []): int
    {
        return $this->ejecutar($sql, $params)->affected_rows;
    }

    public function ultimoId(): int
    {
        return (int) $this->mysqli->insert_id;
    }

    /**
     * Ejecuta la operación dentro de una transacción (corrige H-08).
     *
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    public function transaccion(callable $operacion): mixed
    {
        $this->mysqli->begin_transaction();
        try {
            $resultado = $operacion();
            $this->mysqli->commit();
            return $resultado;
        } catch (\Throwable $e) {
            $this->mysqli->rollback();
            throw $e;
        }
    }
}
