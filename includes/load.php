
<?php
// -----------------------------------------------------------------------
// DEFINE SEPERATOR ALIASES
// -----------------------------------------------------------------------
define("URL_SEPARATOR", '/');

define("DS", DIRECTORY_SEPARATOR);

// -----------------------------------------------------------------------
// DEFINE ROOT PATHS
// -----------------------------------------------------------------------
defined('SITE_ROOT')? null: define('SITE_ROOT', realpath(dirname(__FILE__)));
define("LIB_PATH_INC", SITE_ROOT.DS);


require_once(LIB_PATH_INC.'config.php');
require_once(LIB_PATH_INC.'functions.php');
require_once(LIB_PATH_INC.'session.php');
require_once(LIB_PATH_INC.'upload.php');
require_once(LIB_PATH_INC.'database.php');
require_once(LIB_PATH_INC.'sql.php');

// -----------------------------------------------------------------------
// CÓDIGO NUEVO (src/): carga automática de las clases App\...
// -----------------------------------------------------------------------
spl_autoload_register(function ($clase) {
  if (strpos($clase, 'App\\') === 0) {
    require SITE_ROOT . DS . '..' . DS . 'src' . DS . str_replace('\\', DS, substr($clase, 4)) . '.php';
  }
});
$conexion = new App\Database\Conexion($db->conexion());

// Toda petición POST debe traer un token CSRF válido (corrige H-03).
App\Security\Csrf::protegerPeticion();

?>
