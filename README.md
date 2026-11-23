# Inventario PHP modernizado

Modernización de un sistema real de inventario y ventas escrito en **PHP plano + MySQL** en 2015:
[OSWA-INV](https://github.com/siamon123/warehouse-inventory-system), de Siamon Hasan (licencia MIT),
sin mantenimiento desde 2017.

El objetivo es mostrar cómo se trabaja con código legado que no escribiste: entenderlo, auditarlo,
protegerlo con pruebas, corregirlo y migrarlo de forma gradual a una arquitectura moderna con
**Python (FastAPI)** y **React + TypeScript**, sin detener el sistema.

> 🚧 Proyecto en progreso. Estado actual: **Fase 1, auditoría y pruebas de caracterización**.

## Hoja de ruta

| Fase | Descripción | Estado |
|---|---|---|
| 0 | Sistema original, ejecutable con Docker | ✅ |
| 1 | [Auditoría](AUDITORIA.md) y pruebas de caracterización con PHPUnit | ✅ |
| 2 | Corrección de seguridad y lógica de negocio; refactor en capas | ⏳ |
| 3 | Migración del módulo de productos a FastAPI | ⏳ |
| 4 | Nueva interfaz de productos en React + TypeScript | ⏳ |
| 5 | Convivencia de ambos sistemas detrás de nginx | ⏳ |
| 6 | Integración continua con GitHub Actions | ⏳ |

## Hallazgos principales

La [auditoría completa](AUDITORIA.md) detalla 10 hallazgos. Los más graves:

- 🔴 Contraseñas guardadas con **SHA-1 sin sal**.
- 🟠 **Sin protección CSRF**: un enlace GET borra productos, ventas y usuarios.
- 🟠 El **total de la venta lo decide el navegador**: se pueden vender 3 baterías por Gs. 1.
- 🟠 Se puede vender **más de lo que hay en stock**.

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
OK (12 tests, 21 assertions)
```

## Créditos

Sistema original: **OSWA-INV** de Siamon Hasan, publicado bajo licencia MIT
(ver [LICENSE](LICENSE) y el [README original](README-original.md)).
