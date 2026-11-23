<?php

namespace Tests\Unitarias;

use App\Database\Transacciones;
use App\Ventas\ProductoRepositorio;
use App\Ventas\RegistroDeVentas;
use App\Ventas\StockInsuficiente;
use App\Ventas\VentaInvalida;
use App\Ventas\VentaRepositorio;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias de las reglas de venta, sin base de datos:
 * los repositorios se reemplazan por versiones en memoria.
 */
final class RegistroDeVentasTest extends TestCase
{
    private ProductosEnMemoria $productos;
    private VentasEnMemoria $ventas;
    private RegistroDeVentas $registro;

    protected function setUp(): void
    {
        $this->productos = new ProductosEnMemoria([
            7 => ['id' => 7, 'name' => 'Batería 60Ah', 'sale_price' => '890000.00', 'quantity' => 4],
        ]);
        $this->ventas = new VentasEnMemoria();
        $this->registro = new RegistroDeVentas($this->productos, $this->ventas, new SinTransaccion());
    }

    public function testCalculaElTotalConElPrecioGuardado(): void
    {
        $venta = $this->registro->registrar(7, 3, new \DateTimeImmutable('2025-10-05 10:00'));

        $this->assertSame('2670000.00', $venta->total);
        $this->assertSame(1, $this->productos->stock(7));
        $this->assertCount(1, $this->ventas->registradas);
    }

    public function testPermiteVenderTodoElStockDisponible(): void
    {
        $this->registro->registrar(7, 4, new \DateTimeImmutable());

        $this->assertSame(0, $this->productos->stock(7));
    }

    public function testRechazaVenderMasQueElStockSinRegistrarNada(): void
    {
        $this->expectException(StockInsuficiente::class);

        try {
            $this->registro->registrar(7, 5, new \DateTimeImmutable());
        } finally {
            $this->assertSame(4, $this->productos->stock(7));
            $this->assertCount(0, $this->ventas->registradas);
        }
    }

    public function testRechazaCantidadesCeroONegativas(): void
    {
        $this->expectException(VentaInvalida::class);

        $this->registro->registrar(7, 0, new \DateTimeImmutable());
    }

    public function testRechazaProductosInexistentes(): void
    {
        $this->expectException(VentaInvalida::class);

        $this->registro->registrar(99, 1, new \DateTimeImmutable());
    }

    public function testMultiplicaPreciosSinErroresDeRedondeo(): void
    {
        $this->assertSame('0.30', RegistroDeVentas::multiplicar('0.10', 3));
        $this->assertSame('37.50', RegistroDeVentas::multiplicar('12.5', 3));
        $this->assertSame('2670000.00', RegistroDeVentas::multiplicar('890000.00', 3));
    }
}

final class ProductosEnMemoria implements ProductoRepositorio
{
    /** @param array<int, array{id: int, name: string, sale_price: string, quantity: int}> $productos */
    public function __construct(private array $productos)
    {
    }

    public function buscar(int $id): ?array
    {
        return $this->productos[$id] ?? null;
    }

    public function descontarStock(int $id, int $cantidad): bool
    {
        if (($this->productos[$id]['quantity'] ?? 0) < $cantidad) {
            return false;
        }
        $this->productos[$id]['quantity'] -= $cantidad;
        return true;
    }

    public function stock(int $id): int
    {
        return $this->productos[$id]['quantity'];
    }
}

final class VentasEnMemoria implements VentaRepositorio
{
    /** @var list<array{0: int, 1: int, 2: string, 3: string}> */
    public array $registradas = [];

    public function registrar(int $productoId, int $cantidad, string $total, string $fecha): int
    {
        $this->registradas[] = [$productoId, $cantidad, $total, $fecha];
        return count($this->registradas);
    }
}

final class SinTransaccion implements Transacciones
{
    public function transaccion(callable $operacion): mixed
    {
        return $operacion();
    }
}
