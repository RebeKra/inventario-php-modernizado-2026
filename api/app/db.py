"""Conexión a la misma base de datos MySQL que usa el sistema PHP."""

from collections.abc import Iterator

from sqlalchemy import create_engine
from sqlalchemy.orm import Session, sessionmaker

from app.config import configuracion

motor = create_engine(configuracion.url_base_de_datos, pool_pre_ping=True)
FabricaDeSesiones = sessionmaker(bind=motor, autoflush=False, expire_on_commit=False)


def obtener_sesion() -> Iterator[Session]:
    sesion = FabricaDeSesiones()
    try:
        yield sesion
    finally:
        sesion.close()
