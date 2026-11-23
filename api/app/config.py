"""Configuración leída de variables de entorno (las mismas que usa el sistema PHP)."""

from pydantic_settings import BaseSettings


class Configuracion(BaseSettings):
    db_host: str = "localhost"
    db_port: int = 3306
    db_user: str = "admin"
    db_pass: str = "root"
    db_name: str = "oswa_inv"

    @property
    def url_base_de_datos(self) -> str:
        return (
            f"mysql+pymysql://{self.db_user}:{self.db_pass}"
            f"@{self.db_host}:{self.db_port}/{self.db_name}?charset=utf8mb4"
        )


configuracion = Configuracion()
