"""
Modelos ORM mapeados sobre las tablas existentes.

No se crean ni se renombran tablas: la API nueva y el sistema PHP comparten la base
de datos mientras dure la migración (ver la sección de decisiones del README).
"""

from datetime import date, datetime
from decimal import Decimal

from sqlalchemy import Date, DateTime, ForeignKey, Integer, Numeric, String
from sqlalchemy.orm import DeclarativeBase, Mapped, mapped_column, relationship


class Base(DeclarativeBase):
    pass


class Categoria(Base):
    __tablename__ = "categories"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    nombre: Mapped[str] = mapped_column("name", String(60))


class Producto(Base):
    __tablename__ = "products"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    nombre: Mapped[str] = mapped_column("name", String(255))
    stock: Mapped[int] = mapped_column("quantity", Integer)
    precio_compra: Mapped[Decimal | None] = mapped_column("buy_price", Numeric(25, 2))
    precio_venta: Mapped[Decimal] = mapped_column("sale_price", Numeric(25, 2))
    categoria_id: Mapped[int] = mapped_column("categorie_id", ForeignKey("categories.id"))
    imagen_id: Mapped[int] = mapped_column("media_id", Integer, default=0)
    fecha: Mapped[datetime] = mapped_column("date", DateTime)

    categoria: Mapped[Categoria] = relationship(lazy="joined")


class Venta(Base):
    __tablename__ = "sales"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    producto_id: Mapped[int] = mapped_column("product_id", ForeignKey("products.id"))
    cantidad: Mapped[int] = mapped_column("qty", Integer)
    total: Mapped[Decimal] = mapped_column("price", Numeric(25, 2))
    fecha: Mapped[date] = mapped_column("date", Date)
