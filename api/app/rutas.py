"""Endpoints HTTP del módulo de productos."""

from typing import Annotated

from fastapi import APIRouter, Depends, Query, Response, status
from sqlalchemy.orm import Session

from app.db import obtener_sesion
from app.esquemas import CategoriaSalida, PaginaDeProductos, ProductoEntrada, ProductoSalida
from app.servicio import ServicioDeProductos

router = APIRouter(prefix="/api/v2", tags=["productos"])


def servicio(sesion: Annotated[Session, Depends(obtener_sesion)]) -> ServicioDeProductos:
    return ServicioDeProductos(sesion)


Servicio = Annotated[ServicioDeProductos, Depends(servicio)]


@router.get("/productos", response_model=PaginaDeProductos)
def listar_productos(
    servicio: Servicio,
    buscar: Annotated[str | None, Query(max_length=100)] = None,
    categoria_id: Annotated[int | None, Query(gt=0)] = None,
    pagina: Annotated[int, Query(ge=1)] = 1,
    por_pagina: Annotated[int, Query(ge=1, le=100)] = 20,
):
    items, total = servicio.listar(buscar, categoria_id, pagina, por_pagina)
    return PaginaDeProductos(items=items, total=total, pagina=pagina, por_pagina=por_pagina)


@router.get("/productos/{producto_id}", response_model=ProductoSalida)
def obtener_producto(producto_id: int, servicio: Servicio):
    return servicio.obtener(producto_id)


@router.post("/productos", response_model=ProductoSalida, status_code=status.HTTP_201_CREATED)
def crear_producto(datos: ProductoEntrada, servicio: Servicio):
    return servicio.crear(datos)


@router.put("/productos/{producto_id}", response_model=ProductoSalida)
def actualizar_producto(producto_id: int, datos: ProductoEntrada, servicio: Servicio):
    return servicio.actualizar(producto_id, datos)


@router.delete("/productos/{producto_id}", status_code=status.HTTP_204_NO_CONTENT)
def eliminar_producto(producto_id: int, servicio: Servicio):
    servicio.eliminar(producto_id)
    return Response(status_code=status.HTTP_204_NO_CONTENT)


@router.get("/categorias", response_model=list[CategoriaSalida])
def listar_categorias(servicio: Servicio):
    return servicio.categorias()
