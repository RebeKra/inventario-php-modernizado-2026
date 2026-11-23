import type {
  Categoria,
  FiltrosDeProductos,
  PaginaDeProductos,
  Producto,
  ProductoEntrada,
} from "../tipos";

const BASE = "/api/v2";

/** Error de validación de FastAPI: indica el campo y el motivo. */
interface ErrorDeCampo {
  loc: (string | number)[];
  msg: string;
}

export class ErrorDeApi extends Error {
  constructor(
    public readonly status: number,
    mensaje: string,
    /** Errores por campo cuando la API responde 422 con validación de Pydantic. */
    public readonly campos: Record<string, string> = {},
  ) {
    super(mensaje);
    this.name = "ErrorDeApi";
  }
}

/**
 * Token CSRF de la sesión. El sistema PHP lo publica en la cookie XSRF-TOKEN
 * y la puerta de entrada lo verifica en las peticiones que modifican datos (Fase 5).
 */
export function tokenCsrf(): string | undefined {
  return document.cookie
    .split("; ")
    .find((c) => c.startsWith("XSRF-TOKEN="))
    ?.split("=")[1];
}

async function pedir<T>(ruta: string, opciones: RequestInit = {}): Promise<T> {
  const cabeceras = new Headers(opciones.headers);
  cabeceras.set("Accept", "application/json");
  if (opciones.body) cabeceras.set("Content-Type", "application/json");
  const token = tokenCsrf();
  if (token && opciones.method && opciones.method !== "GET") {
    cabeceras.set("X-CSRF-Token", decodeURIComponent(token));
  }

  const respuesta = await fetch(BASE + ruta, {
    ...opciones,
    headers: cabeceras,
    credentials: "same-origin",
  });

  if (respuesta.status === 204) return undefined as T;

  const cuerpo = await respuesta.json().catch(() => null);
  if (!respuesta.ok) throw convertirError(respuesta.status, cuerpo);
  return cuerpo as T;
}

function convertirError(status: number, cuerpo: unknown): ErrorDeApi {
  const detalle = (cuerpo as { detail?: unknown } | null)?.detail;

  if (typeof detalle === "string") return new ErrorDeApi(status, detalle);

  if (Array.isArray(detalle)) {
    const campos: Record<string, string> = {};
    for (const error of detalle as ErrorDeCampo[]) {
      campos[String(error.loc.at(-1))] = error.msg;
    }
    return new ErrorDeApi(status, "Revisa los datos del formulario.", campos);
  }

  if (status === 401 || status === 403) {
    return new ErrorDeApi(status, "Tu sesión expiró o no tienes permiso. Vuelve a iniciar sesión.");
  }
  return new ErrorDeApi(status, "Ocurrió un error inesperado. Inténtalo de nuevo.");
}

export function listarProductos(filtros: FiltrosDeProductos = {}): Promise<PaginaDeProductos> {
  const parametros = new URLSearchParams();
  for (const [clave, valor] of Object.entries(filtros)) {
    if (valor !== undefined && valor !== "") parametros.set(clave, String(valor));
  }
  const consulta = parametros.toString();
  return pedir(`/productos${consulta ? `?${consulta}` : ""}`);
}

export function listarCategorias(): Promise<Categoria[]> {
  return pedir("/categorias");
}

export function crearProducto(datos: ProductoEntrada): Promise<Producto> {
  return pedir("/productos", { method: "POST", body: JSON.stringify(datos) });
}

export function actualizarProducto(id: number, datos: ProductoEntrada): Promise<Producto> {
  return pedir(`/productos/${id}`, { method: "PUT", body: JSON.stringify(datos) });
}

export function eliminarProducto(id: number): Promise<void> {
  return pedir(`/productos/${id}`, { method: "DELETE" });
}
