import { afterEach, describe, expect, it, vi } from "vitest";
import { crearProducto, ErrorDeApi, listarProductos } from "./cliente";

function respuesta(status: number, cuerpo: unknown) {
  return new Response(JSON.stringify(cuerpo), { status, headers: { "Content-Type": "application/json" } });
}

afterEach(() => {
  vi.unstubAllGlobals();
  document.cookie = "XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
});

describe("cliente de la API", () => {
  it("arma la consulta solo con los filtros que tienen valor", async () => {
    const fetch = vi.fn().mockResolvedValue(respuesta(200, { items: [], total: 0, pagina: 1, por_pagina: 10 }));
    vi.stubGlobal("fetch", fetch);

    await listarProductos({ buscar: "filtro", categoria_id: undefined, pagina: 2 });

    expect(fetch.mock.calls[0][0]).toBe("/api/v2/productos?buscar=filtro&pagina=2");
  });

  it("envía el token CSRF de la cookie en las peticiones que modifican datos", async () => {
    document.cookie = "XSRF-TOKEN=abc123";
    const fetch = vi.fn().mockResolvedValue(respuesta(201, {}));
    vi.stubGlobal("fetch", fetch);

    await crearProducto({ nombre: "X", categoria_id: 1, stock: 1, precio_compra: "1", precio_venta: "2" });

    const cabeceras = fetch.mock.calls[0][1].headers as Headers;
    expect(cabeceras.get("X-CSRF-Token")).toBe("abc123");
  });

  it("convierte los errores de negocio en un mensaje legible", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(respuesta(409, { detail: "Ya existe un producto con ese nombre." })));

    await expect(
      crearProducto({ nombre: "X", categoria_id: 1, stock: 1, precio_compra: "1", precio_venta: "2" }),
    ).rejects.toMatchObject({ status: 409, message: "Ya existe un producto con ese nombre." });
  });

  it("asocia los errores de validación de la API a cada campo", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        respuesta(422, { detail: [{ loc: ["body", "stock"], msg: "Input should be greater than or equal to 0" }] }),
      ),
    );

    const error = await crearProducto({ nombre: "X", categoria_id: 1, stock: -1, precio_compra: "1", precio_venta: "2" }).catch(
      (e: unknown) => e,
    );

    expect(error).toBeInstanceOf(ErrorDeApi);
    expect((error as ErrorDeApi).campos).toEqual({ stock: "Input should be greater than or equal to 0" });
  });
});
