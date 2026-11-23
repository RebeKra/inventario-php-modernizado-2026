# Inventario PHP modernizado

Modernización de un sistema real de inventario y ventas escrito en **PHP plano + MySQL** en 2015:
[OSWA-INV](https://github.com/siamon123/warehouse-inventory-system), de Siamon Hasan (licencia MIT),
sin mantenimiento desde 2017.

El objetivo es mostrar cómo se trabaja con código legado que no escribiste: entenderlo, auditarlo,
protegerlo con pruebas, corregirlo y migrarlo de forma gradual a una arquitectura moderna con
**Python (FastAPI)** y **React + TypeScript**, sin detener el sistema.

> 🚧 Proyecto en progreso. Estado actual: **Fase 3, módulo de productos migrado a FastAPI**.

## Hoja de ruta

| Fase | Descripción | Estado |
|---|---|---|
| 0 | Sistema original, ejecutable con Docker | ✅ |
| 1 | [Auditoría](AUDITORIA.md) y pruebas de caracterización con PHPUnit | ✅ |
| 2 | Corrección de seguridad y lógica de negocio; primeras capas en `src/` | ✅ |
| 3 | Migración del módulo de productos a FastAPI | ✅ |
| 4 | Nueva interfaz de productos en React + TypeScript | ⏳ |
| 5 | Convivencia de ambos sistemas detrás de nginx | ⏳ |
| 6 | Integración continua con GitHub Actions | ⏳ |

## Hallazgos y correcciones

La [auditoría completa](AUDITORIA.md) detalla 13 hallazgos y su estado. Los más graves, ya corregidos:

| Antes | Después |
|---|---|
| 🔴 Contraseñas con **SHA-1 sin sal** | bcrypt, con **migración transparente** al iniciar sesión |
| 🔴 Cualquier usuario podía **cambiar la contraseña de otro** | Se usa siempre el usuario de la sesión |
| 🟠 **Sin CSRF**: un enlace GET borraba datos | Borrado solo por POST con token CSRF y confirmación |
| 🟠 El **navegador decidía el precio**: 3 baterías por Gs. 1 | El total se calcula en el servidor |
| 🟠 Se podía **vender más que el stock** | Descuento atómico (`WHERE quantity >= ?`) dentro de una transacción |

## Arquitectura

El código nuevo vive en `src/` y convive con el original: las páginas antiguas lo van
usando de a poco, sin reescribir todo de golpe.

```
src/
├── Database/   Conexion (consultas preparadas y transacciones)
├── Security/   Contrasenas (bcrypt + migración de SHA-1), Csrf
├── Auth/       Autenticacion + UsuarioRepositorio
└── Ventas/     RegistroDeVentas (reglas de negocio) + repositorios
```

- **Las reglas de negocio no dependen de la base de datos:** `RegistroDeVentas` recibe interfaces
  de repositorio, así que se prueba con versiones en memoria, sin MySQL.
- **Las páginas antiguas solo delegan:** por ejemplo, `add_sale.php` ahora llama a
  `RegistroDeVentas` en vez de armar el SQL a mano.
- **Cambios de esquema versionados:** `database/migraciones/` (por ejemplo, el stock pasa de texto a entero).

## API de productos (Python + FastAPI)

Primer módulo migrado con el patrón **strangler fig**: la API nueva y el sistema PHP
funcionan al mismo tiempo sobre la misma base de datos, y los módulos se van moviendo de a uno.

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/v2/productos?buscar=&categoria_id=&pagina=&por_pagina=` | Listado con búsqueda y paginación |
| `GET` | `/api/v2/productos/{id}` | Detalle de un producto |
| `POST` | `/api/v2/productos` | Alta, con validación (409 si el nombre ya existe) |
| `PUT` | `/api/v2/productos/{id}` | Modificación |
| `DELETE` | `/api/v2/productos/{id}` | Baja (409 si tiene ventas, para no perder historial) |
| `GET` | `/api/v2/categorias` | Categorías |

Documentación interactiva (OpenAPI): http://localhost:8000/api/v2/docs

```
api/app/
├── main.py       Aplicación y manejo de errores
├── rutas.py      Endpoints HTTP
├── esquemas.py   Validación de entrada y salida (Pydantic)
├── servicio.py   Reglas de negocio, independientes de HTTP
└── modelos.py    Mapeo ORM (SQLAlchemy) sobre las tablas existentes
```

## Decisiones técnicas

| Decisión | Por qué | Costo que se acepta |
|---|---|---|
| **Base de datos compartida** entre PHP y la API | Permite migrar módulo por módulo sin sincronizar datos ni detener el sistema | Ambos sistemas quedan acoplados al mismo esquema; se cambia con migraciones versionadas |
| **Mapear las tablas existentes** sin renombrarlas | El sistema PHP sigue funcionando sin cambios | Los nombres en inglés y con errores (`categorie_id`) quedan escondidos detrás del ORM |
| **Importes como texto decimal** (`"890000.00"`) en el JSON | Evita los errores de redondeo de los números con coma flotante | El cliente debe convertirlos para hacer cálculos |
| **Las mismas pruebas de datos** para PHP y Python | Garantiza que ambos sistemas funcionan sobre el mismo esquema | Un cambio en los datos de prueba afecta a las dos suites |
| **Autenticación en la puerta de entrada** (Fase 5) | La sesión vive en PHP: nginx la verificará antes de llegar a la API, sin duplicar el login | Mientras tanto, la API no debe exponerse sin el proxy |

## Cómo ejecutarlo

```bash
docker compose up --build
```

- Sistema PHP: http://localhost:8080 (usuario `admin`, contraseña `admin`)
- API de productos: http://localhost:8000/api/v2/docs

## Pruebas

Las pruebas de caracterización tratan al sistema como una caja negra: levantan el sitio con el
servidor embebido de PHP contra una base de datos de pruebas y hacen peticiones HTTP como un navegador.
Así protegen el comportamiento actual sin tener que modificar el código legado.

Requisitos: PHP 8.2+ con `mysqli` y un MySQL/MariaDB local con un usuario `app` / `app`
(configurable en `phpunit.xml.dist`).

```bash
composer install
composer test
```

```
OK (28 tests, 54 assertions)
```

- `tests/Unitarias`: reglas de negocio y autenticación, sin base de datos.
- `tests/Caracterizacion`: el sistema completo por HTTP, como un navegador.

API en Python (usa la misma base de pruebas y los mismos datos):

```bash
cd api
pip install -r requirements-dev.txt
pytest
```

```
16 passed
```

## Créditos

Sistema original: **OSWA-INV** de Siamon Hasan, publicado bajo licencia MIT
(ver [LICENSE](LICENSE) y el [README original](README-original.md)).
