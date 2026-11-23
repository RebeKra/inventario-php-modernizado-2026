# Inventario PHP modernizado

Modernización de un sistema real de inventario y ventas escrito en **PHP plano + MySQL** en 2015:
[OSWA-INV](https://github.com/siamon123/warehouse-inventory-system), de Siamon Hasan (licencia MIT),
sin mantenimiento desde 2017.

El objetivo es mostrar cómo se trabaja con código legado que no escribiste: entenderlo, auditarlo,
protegerlo con pruebas, corregirlo y migrarlo de forma gradual a una arquitectura moderna con
**Python (FastAPI)** y **React + TypeScript**, sin detener el sistema.

> 🚧 Proyecto en progreso. Estado actual: **Fase 2, correcciones de seguridad y lógica de negocio**.

## Hoja de ruta

| Fase | Descripción | Estado |
|---|---|---|
| 0 | Sistema original, ejecutable con Docker | ✅ |
| 1 | [Auditoría](AUDITORIA.md) y pruebas de caracterización con PHPUnit | ✅ |
| 2 | Corrección de seguridad y lógica de negocio; primeras capas en `src/` | ✅ |
| 3 | Migración del módulo de productos a FastAPI | ⏳ |
| 4 | Nueva interfaz de productos en React + TypeScript | ⏳ |
| 5 | Convivencia de ambos sistemas detrás de nginx | ⏳ |
| 6 | Integración continua con GitHub Actions | ⏳ |

## Hallazgos y correcciones

La [auditoría completa](AUDITORIA.md) detalla 12 hallazgos y su estado. Los más graves, ya corregidos:

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

## Cómo ejecutarlo

```bash
docker compose up --build
```

Abre http://localhost:8080 e inicia sesión con `admin` / `admin`.

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
OK (27 tests, 52 assertions)
```

- `tests/Unitarias`: reglas de negocio y autenticación, sin base de datos.
- `tests/Caracterizacion`: el sistema completo por HTTP, como un navegador.

## Créditos

Sistema original: **OSWA-INV** de Siamon Hasan, publicado bajo licencia MIT
(ver [LICENSE](LICENSE) y el [README original](README-original.md)).
