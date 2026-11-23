/** Contratos de la API de productos (ver api/app/esquemas.py). */

export interface Categoria {
  id: number;
  nombre: string;
}

export interface Producto {
  id: number;
  nombre: string;
  stock: number;
  /** Importe decimal como texto, por ejemplo "890000.00". */
  precio_compra: string | null;
  precio_venta: string;
  categoria: Categoria;
}

export interface ProductoEntrada {
  nombre: string;
  categoria_id: number;
  stock: number;
  precio_compra: string;
  precio_venta: string;
}

export interface PaginaDeProductos {
  items: Producto[];
  total: number;
  pagina: number;
  por_pagina: number;
}

export interface FiltrosDeProductos {
  buscar?: string;
  categoria_id?: number;
  pagina?: number;
  por_pagina?: number;
}
