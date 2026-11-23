"""Reglas de negocio del módulo de productos, independientes de HTTP."""

from datetime import datetime

from sqlalchemy import func, select
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Session

from app.esquemas import ProductoEntrada
from app.modelos import Categoria, Producto, Venta


class ErrorDeNegocio(Exception):
    """Error que se traduce en una respuesta HTTP con un mensaje para el usuario."""

    codigo_http = 400

    def __init__(self, mensaje: str):
        super().__init__(mensaje)
        self.mensaje = mensaje


class NoEncontrado(ErrorDeNegocio):
    codigo_http = 404


class Conflicto(ErrorDeNegocio):
    codigo_http = 409


class DatosInvalidos(ErrorDeNegocio):
    codigo_http = 422


class ServicioDeProductos:
    def __init__(self, sesion: Session):
        self.sesion = sesion

    def listar(self, buscar: str | None, categoria_id: int | None, pagina: int, por_pagina: int):
        consulta = select(Producto)
        if buscar:
            # El texto va como parámetro: SQLAlchemy genera una consulta preparada.
            consulta = consulta.where(Producto.nombre.contains(buscar, autoescape=True))
        if categoria_id:
            consulta = consulta.where(Producto.categoria_id == categoria_id)

        total = self.sesion.scalar(select(func.count()).select_from(consulta.subquery()))
        items = self.sesion.scalars(
            consulta.order_by(Producto.nombre).limit(por_pagina).offset((pagina - 1) * por_pagina)
        ).all()
        return items, total

    def obtener(self, producto_id: int) -> Producto:
        producto = self.sesion.get(Producto, producto_id)
        if not producto:
            raise NoEncontrado("El producto no existe.")
        return producto

    def crear(self, datos: ProductoEntrada) -> Producto:
        self._validar_categoria(datos.categoria_id)
        producto = Producto(
            nombre=datos.nombre,
            categoria_id=datos.categoria_id,
            stock=datos.stock,
            precio_compra=datos.precio_compra,
            precio_venta=datos.precio_venta,
            imagen_id=0,
            fecha=datetime.now().replace(microsecond=0),
        )
        self.sesion.add(producto)
        self._guardar()
        return self.obtener(producto.id)

    def actualizar(self, producto_id: int, datos: ProductoEntrada) -> Producto:
        producto = self.obtener(producto_id)
        self._validar_categoria(datos.categoria_id)
        producto.nombre = datos.nombre
        producto.categoria_id = datos.categoria_id
        producto.stock = datos.stock
        producto.precio_compra = datos.precio_compra
        producto.precio_venta = datos.precio_venta
        self._guardar()
        self.sesion.refresh(producto)
        return producto

    def eliminar(self, producto_id: int) -> None:
        producto = self.obtener(producto_id)
        # En la base original, borrar un producto borra en cascada todo su historial
        # de ventas (H-13). La API nueva no lo permite: protege los registros contables.
        tiene_ventas = self.sesion.scalar(
            select(func.count()).select_from(Venta).where(Venta.producto_id == producto_id)
        )
        if tiene_ventas:
            raise Conflicto(
                "No se puede eliminar un producto con ventas registradas: se perdería el historial."
            )
        self.sesion.delete(producto)
        self.sesion.commit()

    def categorias(self) -> list[Categoria]:
        return list(self.sesion.scalars(select(Categoria).order_by(Categoria.nombre)).all())

    def _validar_categoria(self, categoria_id: int) -> None:
        if not self.sesion.get(Categoria, categoria_id):
            raise DatosInvalidos("La categoría no existe.")

    def _guardar(self) -> None:
        try:
            self.sesion.commit()
        except IntegrityError as error:
            self.sesion.rollback()
            if "Duplicate" in str(error.orig):
                raise Conflicto("Ya existe un producto con ese nombre.") from error
            raise
