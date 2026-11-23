"""Contratos de entrada y salida de la API (validación con Pydantic)."""

from decimal import Decimal

from pydantic import BaseModel, ConfigDict, Field, field_validator

PrecioPositivo = Field(ge=0, max_digits=25, decimal_places=2)


class CategoriaSalida(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: int
    nombre: str


class ProductoEntrada(BaseModel):
    nombre: str = Field(min_length=1, max_length=255)
    categoria_id: int = Field(gt=0)
    stock: int = Field(ge=0)
    precio_compra: Decimal = PrecioPositivo
    precio_venta: Decimal = PrecioPositivo

    @field_validator("nombre")
    @classmethod
    def nombre_sin_espacios_extra(cls, valor: str) -> str:
        valor = " ".join(valor.split())
        if not valor:
            raise ValueError("El nombre no puede estar vacío")
        return valor


class ProductoSalida(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: int
    nombre: str
    stock: int
    precio_compra: Decimal | None
    precio_venta: Decimal
    categoria: CategoriaSalida


class PaginaDeProductos(BaseModel):
    items: list[ProductoSalida]
    total: int
    pagina: int
    por_pagina: int
