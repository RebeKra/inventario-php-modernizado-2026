<?php
/*
|--------------------------------------------------------------------------
| Verificación de sesión para la puerta de entrada (nginx auth_request)
|--------------------------------------------------------------------------
| nginx consulta este archivo antes de dejar pasar una petición a la API o a
| la interfaz nueva. Así, el inicio de sesión del sistema PHP protege también
| a los módulos migrados, sin duplicar usuarios ni contraseñas.
|
| No es accesible desde afuera: nginx lo bloquea y solo lo usa internamente.
|
|   204 → puede continuar
|   401 → no inició sesión
|   403 → sin permiso, o una modificación sin token CSRF válido
*/
// Todo lo que se imprima queda en un búfer: así el código de estado siempre se
// puede enviar, aunque algún archivo incluido escriba texto antes (H-16).
ob_start();
require_once('includes/load.php');
header('Cache-Control: no-store');

// Importante: nginx solo deja pasar con 2xx. Cualquier fallo (por ejemplo, la base
// de datos caída responde 503) deja el acceso cerrado, nunca abierto (H-15).

// Nivel mínimo para el módulo de productos (el mismo que exigía product.php).
const NIVEL_PRODUCTOS = 2;

if (!$session->isUserLoggedIn(true)) {
  http_response_code(401);
  exit;
}

$usuario = current_user();
$grupo = find_by_groupLevel($usuario['user_level']);
if ($grupo['group_status'] === '0' || (int)$usuario['user_level'] > NIVEL_PRODUCTOS) {
  http_response_code(403);
  exit;
}

$metodo = strtoupper($_SERVER['HTTP_X_ORIGINAL_METHOD'] ?? 'GET');
if (!in_array($metodo, array('GET', 'HEAD', 'OPTIONS'), true)
    && !App\Security\Csrf::esValido($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
  http_response_code(403);
  exit;
}

header('X-Usuario-Id: ' . (int)$usuario['id']);
http_response_code(204);
