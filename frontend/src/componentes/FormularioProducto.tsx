import { useState, type FormEvent } from "react";
import { ErrorDeApi } from "../api/cliente";
import type { Categoria, Producto, ProductoEntrada } from "../tipos";

interface Props {
  producto: Producto | null;
  categorias: Categoria[];
  onGuardar: (datos: ProductoEntrada) => Promise<void>;
  onCancelar: () => void;
}

type Errores = Partial<Record<keyof ProductoEntrada | "general", string>>;

const IMPORTE = /^\d+(\.\d{1,2})?$/;

/** Validación en el navegador para dar respuesta inmediata; la API vuelve a validar todo. */
export function validar(datos: ProductoEntrada): Errores {
  const errores: Errores = {};
  if (!datos.nombre.trim()) errores.nombre = "El nombre es obligatorio.";
  if (!datos.categoria_id) errores.categoria_id = "Elige una categoría.";
  if (!Number.isInteger(datos.stock) || datos.stock < 0) errores.stock = "El stock debe ser un número entero mayor o igual a 0.";
  if (!IMPORTE.test(datos.precio_compra)) errores.precio_compra = "Ingresa un importe válido, sin puntos de miles.";
  if (!IMPORTE.test(datos.precio_venta)) errores.precio_venta = "Ingresa un importe válido, sin puntos de miles.";
  return errores;
}

function sinDecimalesVacios(importe: string | null): string {
  return (importe ?? "").replace(/\.00$/, "");
}

export function FormularioProducto({ producto, categorias, onGuardar, onCancelar }: Props) {
  const [nombre, setNombre] = useState(producto?.nombre ?? "");
  const [categoriaId, setCategoriaId] = useState(producto?.categoria.id ?? 0);
  const [stock, setStock] = useState(String(producto?.stock ?? 0));
  const [precioCompra, setPrecioCompra] = useState(sinDecimalesVacios(producto?.precio_compra ?? ""));
  const [precioVenta, setPrecioVenta] = useState(sinDecimalesVacios(producto?.precio_venta ?? ""));
  const [errores, setErrores] = useState<Errores>({});
  const [guardando, setGuardando] = useState(false);

  async function enviar(evento: FormEvent) {
    evento.preventDefault();
    const datos: ProductoEntrada = {
      nombre,
      categoria_id: categoriaId,
      stock: Number(stock),
      precio_compra: precioCompra.trim(),
      precio_venta: precioVenta.trim(),
    };

    const encontrados = validar(datos);
    setErrores(encontrados);
    if (Object.keys(encontrados).length > 0) return;

    setGuardando(true);
    try {
      await onGuardar(datos);
    } catch (error) {
      if (error instanceof ErrorDeApi) {
        setErrores({ ...error.campos, general: error.message });
      } else {
        setErrores({ general: "No se pudo guardar. Revisa tu conexión." });
      }
    } finally {
      setGuardando(false);
    }
  }

  const titulo = producto ? "Editar producto" : "Nuevo producto";

  return (
    <div className="fixed inset-0 z-10 flex items-center justify-center bg-slate-900/40 p-4">
      <form
        role="dialog"
        aria-modal="true"
        aria-labelledby="titulo-formulario"
        onSubmit={enviar}
        noValidate
        className="w-full max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-xl"
      >
        <h2 id="titulo-formulario" className="text-lg font-semibold text-slate-900">
          {titulo}
        </h2>

        {errores.general && (
          <p role="alert" className="rounded bg-red-50 p-3 text-sm text-red-800">
            {errores.general}
          </p>
        )}

        <Campo etiqueta="Nombre" error={errores.nombre}>
          {(props) => <input {...props} value={nombre} onChange={(e) => setNombre(e.target.value)} />}
        </Campo>

        <Campo etiqueta="Categoría" error={errores.categoria_id}>
          {(props) => (
            <select {...props} value={categoriaId} onChange={(e) => setCategoriaId(Number(e.target.value))}>
              <option value={0}>Elige una categoría</option>
              {categorias.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.nombre}
                </option>
              ))}
            </select>
          )}
        </Campo>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <Campo etiqueta="Stock" error={errores.stock}>
            {(props) => <input {...props} inputMode="numeric" value={stock} onChange={(e) => setStock(e.target.value)} />}
          </Campo>
          <Campo etiqueta="Precio de compra (Gs.)" error={errores.precio_compra}>
            {(props) => (
              <input {...props} inputMode="decimal" value={precioCompra} onChange={(e) => setPrecioCompra(e.target.value)} />
            )}
          </Campo>
          <Campo etiqueta="Precio de venta (Gs.)" error={errores.precio_venta}>
            {(props) => (
              <input {...props} inputMode="decimal" value={precioVenta} onChange={(e) => setPrecioVenta(e.target.value)} />
            )}
          </Campo>
        </div>

        <div className="flex justify-end gap-2 pt-2">
          <button type="button" onClick={onCancelar} className="rounded px-4 py-2 text-slate-700 hover:bg-slate-100">
            Cancelar
          </button>
          <button
            type="submit"
            disabled={guardando}
            className="rounded bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
          >
            {guardando ? "Guardando…" : "Guardar"}
          </button>
        </div>
      </form>
    </div>
  );
}

interface PropsCampo {
  etiqueta: string;
  error?: string;
  children: (props: {
    id: string;
    className: string;
    "aria-invalid": boolean;
    "aria-describedby"?: string;
  }) => React.ReactNode;
}

function Campo({ etiqueta, error, children }: PropsCampo) {
  const id = `campo-${etiqueta.toLowerCase().replace(/[^a-z]+/g, "-")}`;
  return (
    <div>
      <label htmlFor={id} className="mb-1 block text-sm font-medium text-slate-700">
        {etiqueta}
      </label>
      {children({
        id,
        className: `w-full rounded border px-3 py-2 text-sm ${error ? "border-red-500" : "border-slate-300"}`,
        "aria-invalid": Boolean(error),
        "aria-describedby": error ? `${id}-error` : undefined,
      })}
      {error && (
        <p id={`${id}-error`} className="mt-1 text-xs text-red-700">
          {error}
        </p>
      )}
    </div>
  );
}
