import { useCallback, useEffect, useState } from "react";
import {
  actualizarProducto,
  crearProducto,
  eliminarProducto,
  ErrorDeApi,
  listarCategorias,
  listarProductos,
} from "./api/cliente";
import { FormularioProducto } from "./componentes/FormularioProducto";
import { Paginacion } from "./componentes/Paginacion";
import { TablaProductos } from "./componentes/TablaProductos";
import { useDiferido } from "./hooks/useDiferido";
import type { Categoria, PaginaDeProductos, Producto, ProductoEntrada } from "./tipos";

const POR_PAGINA = 10;

type Formulario = { abierto: false } | { abierto: true; producto: Producto | null };

export function PaginaProductos() {
  const [buscar, setBuscar] = useState("");
  const [categoriaId, setCategoriaId] = useState<number | undefined>();
  const [pagina, setPagina] = useState(1);
  const [datos, setDatos] = useState<PaginaDeProductos | null>(null);
  const [categorias, setCategorias] = useState<Categoria[]>([]);
  const [cargando, setCargando] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [aviso, setAviso] = useState<string | null>(null);
  const [formulario, setFormulario] = useState<Formulario>({ abierto: false });

  const busqueda = useDiferido(buscar);

  const cargar = useCallback(async () => {
    setCargando(true);
    setError(null);
    try {
      setDatos(
        await listarProductos({ buscar: busqueda, categoria_id: categoriaId, pagina, por_pagina: POR_PAGINA }),
      );
    } catch (e) {
      setError(e instanceof ErrorDeApi ? e.message : "No se pudo conectar con el servidor.");
    } finally {
      setCargando(false);
    }
  }, [busqueda, categoriaId, pagina]);

  useEffect(() => {
    void cargar();
  }, [cargar]);

  useEffect(() => {
    listarCategorias().then(setCategorias).catch(() => setCategorias([]));
  }, []);

  // Al cambiar los filtros se vuelve a la primera página.
  useEffect(() => setPagina(1), [busqueda, categoriaId]);

  async function guardar(entrada: ProductoEntrada) {
    if (formulario.abierto && formulario.producto) {
      await actualizarProducto(formulario.producto.id, entrada);
      setAviso(`Se actualizó "${entrada.nombre}".`);
    } else {
      await crearProducto(entrada);
      setAviso(`Se creó "${entrada.nombre}".`);
    }
    setFormulario({ abierto: false });
    await cargar();
  }

  async function eliminar(producto: Producto) {
    if (!window.confirm(`¿Eliminar "${producto.nombre}"? Esta acción no se puede deshacer.`)) return;
    try {
      await eliminarProducto(producto.id);
      setAviso(`Se eliminó "${producto.nombre}".`);
      await cargar();
    } catch (e) {
      setError(e instanceof ErrorDeApi ? e.message : "No se pudo eliminar el producto.");
    }
  }

  return (
    <main className="mx-auto max-w-6xl space-y-6 p-6">
      <header className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Productos</h1>
          <p className="text-sm text-slate-500">Módulo migrado a React + FastAPI</p>
        </div>
        <button
          type="button"
          onClick={() => setFormulario({ abierto: true, producto: null })}
          className="rounded bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
        >
          Nuevo producto
        </button>
      </header>

      <div className="flex flex-wrap gap-3">
        <label className="flex-1">
          <span className="sr-only">Buscar productos</span>
          <input
            type="search"
            placeholder="Buscar por nombre…"
            value={buscar}
            onChange={(e) => setBuscar(e.target.value)}
            className="w-full rounded border border-slate-300 px-3 py-2"
          />
        </label>
        <label>
          <span className="sr-only">Filtrar por categoría</span>
          <select
            value={categoriaId ?? ""}
            onChange={(e) => setCategoriaId(e.target.value ? Number(e.target.value) : undefined)}
            className="rounded border border-slate-300 px-3 py-2"
          >
            <option value="">Todas las categorías</option>
            {categorias.map((c) => (
              <option key={c.id} value={c.id}>
                {c.nombre}
              </option>
            ))}
          </select>
        </label>
      </div>

      {aviso && (
        <p role="status" className="flex justify-between rounded bg-emerald-50 p-3 text-sm text-emerald-800">
          {aviso}
          <button type="button" onClick={() => setAviso(null)} aria-label="Cerrar aviso">
            ×
          </button>
        </p>
      )}
      {error && (
        <p role="alert" className="rounded bg-red-50 p-3 text-sm text-red-800">
          {error}
        </p>
      )}

      {cargando && !datos ? (
        <p className="text-slate-500">Cargando productos…</p>
      ) : (
        datos && (
          <>
            <TablaProductos
              productos={datos.items}
              onEditar={(producto) => setFormulario({ abierto: true, producto })}
              onEliminar={eliminar}
            />
            <Paginacion pagina={datos.pagina} porPagina={datos.por_pagina} total={datos.total} onCambiar={setPagina} />
          </>
        )
      )}

      {formulario.abierto && (
        <FormularioProducto
          producto={formulario.producto}
          categorias={categorias}
          onGuardar={guardar}
          onCancelar={() => setFormulario({ abierto: false })}
        />
      )}
    </main>
  );
}
