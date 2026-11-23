interface Props {
  pagina: number;
  porPagina: number;
  total: number;
  onCambiar: (pagina: number) => void;
}

export function Paginacion({ pagina, porPagina, total, onCambiar }: Props) {
  const paginas = Math.max(1, Math.ceil(total / porPagina));
  const desde = total === 0 ? 0 : (pagina - 1) * porPagina + 1;
  const hasta = Math.min(pagina * porPagina, total);

  return (
    <nav aria-label="Paginación" className="flex items-center justify-between text-sm text-slate-600">
      <p>
        {desde}–{hasta} de {total} productos
      </p>
      <div className="flex gap-2">
        <button
          type="button"
          onClick={() => onCambiar(pagina - 1)}
          disabled={pagina <= 1}
          className="rounded border border-slate-300 bg-white px-3 py-1 disabled:opacity-40"
        >
          Anterior
        </button>
        <span className="px-2 py-1">
          Página {pagina} de {paginas}
        </span>
        <button
          type="button"
          onClick={() => onCambiar(pagina + 1)}
          disabled={pagina >= paginas}
          className="rounded border border-slate-300 bg-white px-3 py-1 disabled:opacity-40"
        >
          Siguiente
        </button>
      </div>
    </nav>
  );
}
