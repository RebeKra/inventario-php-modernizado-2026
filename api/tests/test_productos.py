"""Pruebas de la API de productos contra MySQL real."""

NUEVO = {
    "nombre": "Bujía de iridio",
    "categoria_id": 1,
    "stock": 40,
    "precio_compra": "38000",
    "precio_venta": "55000",
}


def test_lista_los_productos_ordenados_por_nombre(cliente):
    respuesta = cliente.get("/api/v2/productos")

    assert respuesta.status_code == 200
    cuerpo = respuesta.json()
    assert cuerpo["total"] == 2
    assert [p["nombre"] for p in cuerpo["items"]] == ["Batería 60Ah", "Filtro de aceite"]
    assert cuerpo["items"][1]["categoria"] == {"id": 1, "nombre": "Filtros"}
    assert cuerpo["items"][1]["stock"] == 30


def test_busca_por_nombre(cliente):
    cuerpo = cliente.get("/api/v2/productos", params={"buscar": "filtro"}).json()

    assert [p["nombre"] for p in cuerpo["items"]] == ["Filtro de aceite"]


def test_la_busqueda_no_permite_inyeccion_sql(cliente):
    cuerpo = cliente.get("/api/v2/productos", params={"buscar": "zzz' OR '1'='1"}).json()

    assert cuerpo["total"] == 0


def test_los_comodines_de_la_busqueda_se_tratan_como_texto(cliente):
    assert cliente.get("/api/v2/productos", params={"buscar": "%"}).json()["total"] == 0


def test_pagina_los_resultados(cliente):
    cuerpo = cliente.get("/api/v2/productos", params={"pagina": 2, "por_pagina": 1}).json()

    assert cuerpo["total"] == 2
    assert [p["nombre"] for p in cuerpo["items"]] == ["Filtro de aceite"]


def test_obtiene_un_producto(cliente):
    respuesta = cliente.get("/api/v2/productos/2")

    assert respuesta.status_code == 200
    assert respuesta.json()["precio_venta"] == "890000.00"


def test_un_producto_inexistente_devuelve_404(cliente):
    respuesta = cliente.get("/api/v2/productos/999")

    assert respuesta.status_code == 404
    assert respuesta.json()["detail"] == "El producto no existe."


def test_crea_un_producto_y_lo_guarda_en_la_base_compartida(cliente, sql):
    respuesta = cliente.post("/api/v2/productos", json=NUEVO)

    assert respuesta.status_code == 201
    assert respuesta.json()["nombre"] == "Bujía de iridio"
    fila = sql("SELECT quantity, sale_price FROM products WHERE name = %s", "Bujía de iridio")[0]
    assert fila["quantity"] == 40
    assert str(fila["sale_price"]) == "55000.00"


def test_no_permite_nombres_repetidos(cliente):
    respuesta = cliente.post("/api/v2/productos", json={**NUEVO, "nombre": "Filtro de aceite"})

    assert respuesta.status_code == 409
    assert respuesta.json()["detail"] == "Ya existe un producto con ese nombre."


def test_valida_los_datos_de_entrada(cliente):
    respuesta = cliente.post(
        "/api/v2/productos",
        json={**NUEVO, "nombre": "   ", "stock": -1, "precio_venta": "-5"},
    )

    assert respuesta.status_code == 422
    campos = {error["loc"][-1] for error in respuesta.json()["detail"]}
    assert campos == {"nombre", "stock", "precio_venta"}


def test_rechaza_una_categoria_inexistente(cliente):
    respuesta = cliente.post("/api/v2/productos", json={**NUEVO, "categoria_id": 99})

    assert respuesta.status_code == 422
    assert respuesta.json()["detail"] == "La categoría no existe."


def test_actualiza_un_producto(cliente, sql):
    respuesta = cliente.put(
        "/api/v2/productos/1", json={**NUEVO, "nombre": "Filtro de aceite premium", "stock": 12}
    )

    assert respuesta.status_code == 200
    fila = sql("SELECT name, quantity FROM products WHERE id = 1")[0]
    assert fila == {"name": "Filtro de aceite premium", "quantity": 12}


def test_elimina_un_producto_sin_ventas(cliente, sql):
    respuesta = cliente.delete("/api/v2/productos/2")

    assert respuesta.status_code == 204
    assert sql("SELECT id FROM products WHERE id = 2") == ()


def test_no_elimina_un_producto_con_ventas_para_no_perder_el_historial(cliente, sql):
    sql("INSERT INTO sales (product_id, qty, price, date) VALUES (1, 2, 90000, '2025-10-05')")

    respuesta = cliente.delete("/api/v2/productos/1")

    assert respuesta.status_code == 409
    assert len(sql("SELECT id FROM sales WHERE product_id = 1")) == 1


def test_lista_las_categorias(cliente):
    assert cliente.get("/api/v2/categorias").json() == [{"id": 1, "nombre": "Filtros"}]


def test_un_producto_creado_por_la_api_aparece_en_el_sistema_php(cliente, sql):
    """Ambos sistemas comparten datos: lo que crea uno, lo ve el otro."""
    cliente.post("/api/v2/productos", json=NUEVO)

    fila = sql(
        "SELECT p.name, c.name AS categoria FROM products p "
        "JOIN categories c ON c.id = p.categorie_id WHERE p.name = %s",
        "Bujía de iridio",
    )[0]
    assert fila == {"name": "Bujía de iridio", "categoria": "Filtros"}
