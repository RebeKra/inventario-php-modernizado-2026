import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import * as api from "./api/cliente";
import { PaginaProductos } from "./PaginaProductos";
import type { Producto } from "./tipos";

vi.mock("./api/cliente", async (original) => ({
  ...(await original<typeof import("./api/cliente")>()),
  listarProductos: vi.fn(),
  listarCategorias: vi.fn(),
  crearProducto: vi.fn(),
  actualizarProducto: vi.fn(),
  eliminarProducto: vi.fn(),
}));

const FILTROS = { id: 1, nombre: "Filtros" };
const BATERIA: Producto = {
  id: 2,
  nombre: "Batería 60Ah",
  stock: 4,
  precio_compra: "700000.00",
  precio_venta: "890000.00",
  categoria: FILTROS,
};
const FILTRO: Producto = { ...BATERIA, id: 1, nombre: "Filtro de aceite", stock: 30, precio_venta: "45000.00" };

beforeEach(() => {
  vi.mocked(api.listarProductos).mockResolvedValue({ items: [BATERIA, FILTRO], total: 2, pagina: 1, por_pagina: 10 });
  vi.mocked(api.listarCategorias).mockResolvedValue([FILTROS]);
});

describe("PaginaProductos", () => {
  it("muestra los productos con el precio formateado y avisa el stock bajo", async () => {
    render(<PaginaProductos />);

    const fila = (await screen.findByText("Batería 60Ah")).closest("tr")!;
    expect(within(fila).getByText("Gs. 890.000")).toBeInTheDocument();
    expect(within(fila).getByText("Stock bajo")).toBeInTheDocument();
    expect(within(screen.getByText("Filtro de aceite").closest("tr")!).queryByText("Stock bajo")).toBeNull();
  });

  it("busca en la API mientras el usuario escribe", async () => {
    render(<PaginaProductos />);
    await screen.findByText("Batería 60Ah");

    await userEvent.type(screen.getByPlaceholderText("Buscar por nombre…"), "filtro");

    await waitFor(() =>
      expect(api.listarProductos).toHaveBeenLastCalledWith(expect.objectContaining({ buscar: "filtro", pagina: 1 })),
    );
  });

  it("valida el formulario antes de enviarlo", async () => {
    render(<PaginaProductos />);
    await userEvent.click(await screen.findByRole("button", { name: "Nuevo producto" }));

    await userEvent.click(screen.getByRole("button", { name: "Guardar" }));

    expect(screen.getByText("El nombre es obligatorio.")).toBeInTheDocument();
    expect(screen.getByText("Elige una categoría.")).toBeInTheDocument();
    expect(api.crearProducto).not.toHaveBeenCalled();
  });

  it("crea un producto y recarga el listado", async () => {
    vi.mocked(api.crearProducto).mockResolvedValue({ ...FILTRO, id: 3, nombre: "Bujía" });
    render(<PaginaProductos />);
    await userEvent.click(await screen.findByRole("button", { name: "Nuevo producto" }));

    await userEvent.type(screen.getByLabelText("Nombre"), "Bujía");
    await userEvent.selectOptions(screen.getByLabelText("Categoría"), "1");
    await userEvent.clear(screen.getByLabelText("Stock"));
    await userEvent.type(screen.getByLabelText("Stock"), "40");
    await userEvent.type(screen.getByLabelText("Precio de compra (Gs.)"), "38000");
    await userEvent.type(screen.getByLabelText("Precio de venta (Gs.)"), "55000");
    await userEvent.click(screen.getByRole("button", { name: "Guardar" }));

    expect(api.crearProducto).toHaveBeenCalledWith({
      nombre: "Bujía",
      categoria_id: 1,
      stock: 40,
      precio_compra: "38000",
      precio_venta: "55000",
    });
    expect(await screen.findByRole("status")).toHaveTextContent('Se creó "Bujía".');
  });

  it("muestra el error de la API dentro del formulario", async () => {
    vi.mocked(api.actualizarProducto).mockRejectedValue(new api.ErrorDeApi(409, "Ya existe un producto con ese nombre."));
    render(<PaginaProductos />);

    await userEvent.click(await screen.findByRole("button", { name: "Editar Batería 60Ah" }));
    await userEvent.click(screen.getByRole("button", { name: "Guardar" }));

    expect(await within(screen.getByRole("dialog")).findByRole("alert")).toHaveTextContent(
      "Ya existe un producto con ese nombre.",
    );
  });

  it("explica por qué no se puede eliminar un producto con ventas", async () => {
    vi.spyOn(window, "confirm").mockReturnValue(true);
    vi.mocked(api.eliminarProducto).mockRejectedValue(
      new api.ErrorDeApi(409, "No se puede eliminar un producto con ventas registradas: se perdería el historial."),
    );
    render(<PaginaProductos />);

    await userEvent.click(await screen.findByRole("button", { name: "Eliminar Filtro de aceite" }));

    expect(await screen.findByRole("alert")).toHaveTextContent("No se puede eliminar un producto con ventas registradas");
  });
});
