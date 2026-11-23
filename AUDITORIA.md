# Auditoría del sistema original

Revisión del código de [OSWA-INV](https://github.com/siamon123/warehouse-inventory-system)
(versión de 2015, sin mantenimiento desde 2017) antes de hacer cualquier cambio.

Cada hallazgo indica dónde está y cómo se comprueba. Los marcados con 🧪 tienen una
**prueba de caracterización** en `tests/Caracterizacion/` que documenta el comportamiento
actual; en la Fase 2 esas pruebas se actualizarán para exigir el comportamiento correcto.

## Resumen

| ID | Hallazgo | Tipo | Gravedad | Estado |
|---|---|---|---|---|
| H-01 | Contraseñas guardadas con SHA-1 sin sal 🧪 | Seguridad | 🔴 Crítica | ✅ Corregido (Fase 2) |
| H-02 | Consultas SQL armadas con concatenación y escapado manual | Seguridad | 🟠 Alta | 🟡 Parcial: el código nuevo usa consultas preparadas |
| H-03 | Sin protección CSRF: un enlace GET elimina productos, ventas y usuarios 🧪 | Seguridad | 🟠 Alta | ✅ Corregido (Fase 2) |
| H-04 | Se puede vender más de lo que hay en stock; el stock queda negativo 🧪 | Negocio | 🟠 Alta | ✅ Corregido (Fase 2) |
| H-05 | El total de la venta lo define el navegador y el servidor no lo recalcula 🧪 | Negocio / Seguridad | 🟠 Alta | ✅ Corregido (Fase 2) |
| H-06 | La sesión no se regenera al iniciar sesión (fijación de sesión) 🧪 | Seguridad | 🟡 Media | ✅ Corregido (Fase 2) |
| H-07 | Los errores de base de datos muestran la consulta SQL completa al usuario | Seguridad | 🟡 Media | ✅ Corregido (Fase 2) |
| H-08 | Venta y descuento de stock sin transacción | Integridad de datos | 🟡 Media | ✅ Corregido (Fase 2) |
| H-09 | El stock se guarda como texto (`VARCHAR`) | Modelo de datos | 🟡 Media | ✅ Corregido con una migración (Fase 2) |
| H-10 | SQL, lógica de negocio y HTML mezclados; estado global en todo el sistema | Mantenibilidad | 🔵 Baja | 🟡 En curso: capas nuevas en `src/` |
| H-11 | Cambio de contraseña de otro usuario modificando el `id` del formulario 🧪 | Seguridad | 🔴 Crítica | ✅ Corregido (Fase 2) |
| H-12 | Editar una venta no ajusta el stock y acepta el precio del navegador | Negocio | 🟠 Alta | ⏳ Pendiente (migración del módulo de ventas) |
| H-13 | Eliminar un producto borra en cascada todo su historial de ventas 🧪 | Integridad de datos | 🟠 Alta | ✅ Corregido (Fase 3) |
| H-14 | El volcado de la base (`oswa_inv.sql`, con los hashes de las contraseñas) y los archivos internos se pueden descargar desde el navegador | Seguridad | 🔴 Crítica | ✅ Corregido en la puerta de entrada (Fase 5) |
| H-15 | Si la base de datos no responde, el sistema contesta 200 (éxito) con el mensaje de error; detrás de nginx, la verificación de sesión lo tomaría como acceso permitido | Seguridad / Disponibilidad | 🟠 Alta | ✅ Corregido (Fase 6) |
| H-16 | Un salto de línea antes de `<?php` en `includes/load.php` envía los encabezados antes de tiempo: sin búfer de salida fallan las redirecciones y los códigos de estado, y la verificación de sesión respondía 200 sin sesión 🧪 | Seguridad | 🔴 Crítica | ✅ Corregido (Fase 6) |

H-11 y H-12 se encontraron durante la Fase 2, al revisar en detalle los archivos que había que modificar.
H-13 se encontró en la Fase 3, al mapear el esquema de la base para la API nueva.
H-14 se encontró en la Fase 5, al definir qué rutas debía publicar la puerta de entrada.
H-15 y H-16 los detectó la prueba de humo de la integración continua (Fase 6), al levantar el sistema con Docker.

## Detalle

### H-01 · Contraseñas con SHA-1 sin sal 🔴
- **Dónde:** `includes/sql.php` → `authenticate()` y `authenticate_v2()`; `add_user.php`, `change_password.php`.
- **Problema:** SHA-1 es un algoritmo rápido y sin sal: las contraseñas comunes se descifran al instante
  con tablas precalculadas. Por ejemplo, `d033e22a...` es `admin`.
- **Prueba:** `AutenticacionTest::testLasContrasenasSeGuardanComoSha1SinSal`.
- **Solución propuesta:** `password_hash()` / `password_verify()` (bcrypt), con migración transparente:
  al iniciar sesión con un hash SHA-1 válido, se vuelve a guardar con el algoritmo nuevo.

### H-02 · SQL por concatenación 🟠
- **Dónde:** prácticamente todo `includes/sql.php` y los formularios (`add_product.php`, `add_sale.php`, etc.).
- **Problema:** la seguridad depende de no olvidar nunca `$db->escape()` o `(int)`. Hoy los puntos revisados
  están escapados, pero un solo olvido abre una inyección SQL. No es un diseño seguro por defecto.
- **Solución propuesta:** consultas preparadas (`mysqli` con parámetros) centralizadas en repositorios.

### H-03 · Sin protección CSRF 🟠
- **Dónde:** `delete_product.php`, `delete_sale.php`, `delete_user.php`, `delete_categorie.php`, todos los formularios.
- **Problema:** basta con que un usuario con sesión abierta visite una página maliciosa con
  `<img src="https://sistema/delete_product.php?id=1">` para borrar datos.
- **Prueba:** `ProductosTest::testUnEnlaceGetEliminaUnProductoSinProteccionCsrf`.
- **Solución propuesta:** las acciones que modifican datos solo por `POST` y con token CSRF.

### H-04 · Stock negativo 🟠
- **Dónde:** `add_sale.php` y `update_product_qty()` en `includes/sql.php`.
- **Problema:** no se valida el stock disponible. Vender 10 baterías con stock 4 deja el stock en `-6`.
- **Prueba:** `VentasTest::testUnaVentaMayorAlStockDejaElStockNegativo`.
- **Solución propuesta:** validar en el servidor y descontar con una actualización condicional
  (`... WHERE quantity >= ?`) dentro de una transacción.

### H-05 · El navegador decide el precio 🟠
- **Dónde:** `add_sale.php` guarda `$_POST['total']` tal cual.
- **Problema:** cualquiera puede modificar el formulario y registrar 3 baterías por Gs. 1.
- **Prueba:** `VentasTest::testElTotalDeLaVentaLoDecideElNavegador`.
- **Solución propuesta:** calcular el total en el servidor a partir del precio guardado del producto.

### H-06 · Fijación de sesión 🟡
- **Dónde:** `includes/session.php` → `login()` no llama a `session_regenerate_id()`.
- **Solución propuesta:** regenerar el identificador de sesión al iniciar y cerrar sesión.

### H-07 · Errores con la consulta completa 🟡
- **Dónde:** `includes/database.php` → `die("Error on this Query :<pre> " . $sql ."</pre>")`.
- **Problema:** expone la estructura de la base de datos a cualquier usuario.
- **Solución propuesta:** registrar el error en el log y mostrar un mensaje genérico.

### H-08 · Sin transacciones 🟡
- **Dónde:** `add_sale.php` inserta la venta y luego, en otra consulta, descuenta el stock.
- **Problema:** si la segunda falla, queda una venta registrada sin descontar stock.
- **Solución propuesta:** ambas operaciones dentro de una transacción.

### H-09 · Stock como texto 🟡
- **Dónde:** `oswa_inv.sql` → `products.quantity VARCHAR(50)`.
- **Problema:** permite valores no numéricos y complica comparaciones y reportes.
- **Solución propuesta:** migración a `INT` con validación previa de los datos existentes.

### H-10 · Mantenibilidad 🔵
- SQL, reglas de negocio y HTML en el mismo archivo; `global $db` y `global $session` en todo el código;
  funciones duplicadas (`authenticate` / `authenticate_v2`); sin pruebas automatizadas.
- **Solución propuesta:** separar en capas (repositorios, servicios y controladores) con inyección de dependencias.

### H-11 · Cambio de contraseña de otro usuario 🔴
- **Dónde:** `change_password.php` usaba `$id = (int)$_POST['id']`, un campo oculto del formulario.
- **Problema:** cualquier usuario con sesión podía cambiar la contraseña del administrador
  editando ese campo (referencia directa insegura a objetos, IDOR).
- **Prueba:** `AutenticacionTest::testNoSePuedeCambiarLaContrasenaDeOtroUsuario`.
- **Solución aplicada:** se usa siempre el id del usuario de la sesión.

### H-12 · Edición de ventas 🟠
- **Dónde:** `edit_sale.php`.
- **Problema:** cambiar la cantidad de una venta no devuelve ni descuenta stock, y el precio
  vuelve a tomarse del formulario.
- **Plan:** se resolverá al migrar el módulo de ventas, reutilizando `RegistroDeVentas`.

### H-13 · Borrado en cascada del historial de ventas 🟠
- **Dónde:** `oswa_inv.sql` → `sales.product_id ... ON DELETE CASCADE`.
- **Problema:** al eliminar un producto se borran todas sus ventas, y los reportes del pasado cambian.
- **Prueba:** `ProductosTest::testNoSeEliminaUnProductoConVentasRegistradas` (PHP) y
  `test_no_elimina_un_producto_con_ventas_para_no_perder_el_historial` (API).
- **Solución aplicada:** ambos sistemas rechazan eliminar un producto con ventas. A futuro conviene
  reemplazar el borrado por una baja lógica (producto inactivo) y quitar la cascada del esquema.

### H-14 · Archivos privados publicados 🔴
- **Dónde:** el sistema se instala en la raíz del sitio web, junto con `oswa_inv.sql`, `includes/` y la configuración.
- **Problema:** cualquiera podía descargar `http://sitio/oswa_inv.sql`, con la estructura de la base y los
  hashes SHA-1 de las contraseñas, que además se descifran fácilmente (H-01).
- **Solución aplicada:** nginx solo publica lo necesario y responde 404 a volcados SQL, carpetas internas,
  documentación, configuración y archivos ocultos (`gateway/nginx.conf`). A futuro conviene mover el código
  fuera de la carpeta pública y dejar solo un punto de entrada.

### H-15 · Errores de conexión con respuesta de éxito 🟠
- **Dónde:** `includes/database.php` → `die("Database connection failed...")`, que responde con código 200.
- **Problema:** para cualquier cliente automático, una base caída parecía una respuesta correcta. Detrás de
  nginx era peor: `auth_request` deja pasar cualquier 2xx, así que con la base caída la verificación de
  sesión habría *abierto* el acceso a la API en lugar de cerrarlo.
- **Cómo se encontró:** al investigar por qué la prueba de humo veía páginas protegidas respondiendo 200 en Docker.
- **Solución aplicada:** la conexión fallida responde 503 sin detalles y queda en el log; la verificación de sesión
  queda cerrada ante cualquier error. Además, Docker Compose espera a que cada servicio esté sano
  (verificaciones de salud reales) antes de arrancar el siguiente.

### H-16 · Encabezados enviados antes de tiempo 🔴
- **Dónde:** `includes/load.php` empezaba con una línea en blanco antes de `<?php`. Como todas las páginas
  lo incluyen primero, ese salto de línea se enviaba al navegador antes que cualquier encabezado.
- **Problema:** el sistema solo funcionaba si PHP tenía activado el búfer de salida (`output_buffering`), que
  la imagen oficial de PHP para Docker trae desactivado. Sin búfer, las redirecciones dejaban de funcionar y
  los códigos de estado se ignoraban: la verificación de sesión respondía **200 aunque no hubiera sesión**, y
  nginx dejaba pasar a la API y a la interfaz nueva a cualquiera.
- **Cómo se encontró:** la prueba de humo en GitHub Actions mostró la API respondiendo 200 sin sesión. Se
  reprodujo localmente apagando el búfer de salida y se ubicó el archivo con `headers_sent()`.
- **Prueba:** las pruebas de caracterización ahora levantan el sistema **sin búfer de salida**, como Docker:
  si alguien vuelve a escribir texto antes de los encabezados, 23 pruebas fallan.
- **Solución aplicada:** se quitó la línea en blanco; la imagen de Docker usa la configuración de producción de
  PHP; y la verificación de sesión guarda su salida en un búfer propio, para fallar cerrada en cualquier caso.

## Estrategia

1. **Primero proteger:** pruebas de caracterización de caja negra (HTTP) sobre los flujos críticos.
   No dependen de la estructura interna, así que siguen siendo válidas durante el refactor.
2. **Después corregir:** cada corrección cambia a propósito la prueba correspondiente,
   de "documenta el defecto" a "exige el comportamiento correcto".
3. **Por último, modernizar** de forma gradual (patrón *strangler fig*), sin detener el sistema.
