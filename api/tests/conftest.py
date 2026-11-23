"""
Las pruebas usan la misma base de datos de pruebas y los mismos datos de ejemplo
que las pruebas de PHP (oswa_inv.sql + migraciones + tests/fixtures.sql), para
verificar que ambos sistemas funcionan sobre exactamente el mismo esquema.
"""

import os
from pathlib import Path

import pymysql
import pytest
from pymysql.constants import CLIENT

RAIZ = Path(__file__).resolve().parents[2]

os.environ.setdefault("DB_HOST", os.getenv("TEST_DB_HOST", "127.0.0.1"))
os.environ.setdefault("DB_USER", os.getenv("TEST_DB_USER", "app"))
os.environ.setdefault("DB_PASS", os.getenv("TEST_DB_PASS", "app"))
os.environ.setdefault("DB_NAME", os.getenv("TEST_DB_NAME", "oswa_inv_test"))

from fastapi.testclient import TestClient  # noqa: E402

from app.db import motor  # noqa: E402
from app.main import app  # noqa: E402


def _archivos_sql() -> list[Path]:
    return [
        RAIZ / "oswa_inv.sql",
        *sorted((RAIZ / "database" / "migraciones").glob("*.sql")),
        RAIZ / "tests" / "fixtures.sql",
    ]


@pytest.fixture(autouse=True)
def base_de_datos_limpia():
    nombre = os.environ["DB_NAME"]
    conexion = pymysql.connect(
        host=os.environ["DB_HOST"],
        user=os.environ["DB_USER"],
        password=os.environ["DB_PASS"],
        client_flag=CLIENT.MULTI_STATEMENTS,
        autocommit=True,
    )
    with conexion.cursor() as cursor:
        cursor.execute(f"DROP DATABASE IF EXISTS `{nombre}`")
        cursor.execute(f"CREATE DATABASE `{nombre}` CHARACTER SET utf8")
        cursor.execute(f"USE `{nombre}`")
        for archivo in _archivos_sql():
            cursor.execute(archivo.read_text(encoding="utf-8"))
            while cursor.nextset():
                pass
    conexion.close()
    motor.dispose()
    yield


@pytest.fixture
def cliente() -> TestClient:
    return TestClient(app)


@pytest.fixture
def sql():
    """Ejecuta una consulta directa para verificar el estado de la base de datos."""

    def ejecutar(consulta: str, *parametros):
        conexion = pymysql.connect(
            host=os.environ["DB_HOST"],
            user=os.environ["DB_USER"],
            password=os.environ["DB_PASS"],
            database=os.environ["DB_NAME"],
            cursorclass=pymysql.cursors.DictCursor,
        )
        with conexion.cursor() as cursor:
            cursor.execute(consulta, parametros)
            filas = cursor.fetchall()
        conexion.commit()
        conexion.close()
        return filas

    return ejecutar
