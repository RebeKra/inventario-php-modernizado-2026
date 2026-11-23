import type { Producto } from "../tipos";
import { esStockBajo, formatearGuaranies } from "../utils/formato";

interface Props {
  productos: Producto[];
  onEditar: (producto: Producto) => void;
  onEliminar: (producto: Producto) => void;
}

export function TablaProductos({ productos, onEditar, onEliminar }: Props) {
  if (productos.length === 0) {
    return (
      <p className="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
        No se encontraron productos.
      </p>
    );
  }

  return (
    <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
      <table className="min-w-full divide-y divide-slate-200 text-sm">
        <thead className="bg-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
          <tr>
            <th scope="col" className="px-4 py-3">Producto</th>
            <th scope="col" className="px-4 py-3">Categoría</th>
            <th scope="col" className="px-4 py-3 text-right">Stock</th>
            <th scope="col" className="px-4 py-3 text-right">Precio de compra</th>
            <th scope="col" className="px-4 py-3 text-right">Precio de venta</th>
            <th scope="col" className="px-4 py-3"><span className="sr-only">Acciones</span></th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {productos.map((producto) => (
            <tr key={producto.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-medium text-slate-900">{producto.nombre}</td>
              <td className="px-4 py-3 text-slate-600">{producto.categoria.nombre}</td>
              <td className="px-4 py-3 text-right tabular-nums">
                {producto.stock}
                {esStockBajo(producto.stock) && (
                  <span className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                    Stock bajo
                  </span>
                )}
              </td>
              <td className="px-4 py-3 text-right tabular-nums text-slate-600">
                {formatearGuaranies(producto.precio_compra)}
              </td>
              <td className="px-4 py-3 text-right tabular-nums">{formatearGuaranies(producto.precio_venta)}</td>
              <td className="whitespace-nowrap px-4 py-3 text-right">
                <button
                  type="button"
                  onClick={() => onEditar(producto)}
                  className="rounded px-2 py-1 text-indigo-700 hover:bg-indigo-50"
                  aria-label={`Editar ${producto.nombre}`}
                >
                  Editar
                </button>
                <button
                  type="button"
                  onClick={() => onEliminar(producto)}
                  className="ml-1 rounded px-2 py-1 text-red-700 hover:bg-red-50"
                  aria-label={`Eliminar ${producto.nombre}`}
                >
                  Eliminar
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
