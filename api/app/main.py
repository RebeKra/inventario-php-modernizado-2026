"""
API de productos (FastAPI).

Primer módulo migrado desde el sistema PHP con el patrón strangler fig: ambos
sistemas conviven y comparten la base de datos mientras dura la migración.
"""

from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse

from app.rutas import router
from app.servicio import ErrorDeNegocio

app = FastAPI(
    title="Inventario: API de productos",
    version="2.0.0",
    docs_url="/api/v2/docs",
    openapi_url="/api/v2/openapi.json",
)
app.include_router(router)


@app.exception_handler(ErrorDeNegocio)
def manejar_error_de_negocio(_: Request, error: ErrorDeNegocio) -> JSONResponse:
    return JSONResponse(status_code=error.codigo_http, content={"detail": error.mensaje})


@app.get("/api/v2/salud", tags=["salud"])
def salud() -> dict[str, str]:
    return {"estado": "ok"}
