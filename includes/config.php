<?php
/*
|--------------------------------------------------------------------------
| OWSA-INV V2
|--------------------------------------------------------------------------
| Author: Siamon Hasan
| Project Name: OSWA-INV
| Version: v2
| Offcial page: http://oswapp.com/
| facebook Page: https://www.facebook.com/oswapp
|
|
|
*/
  // Las credenciales se leen de variables de entorno para poder usar otra base
  // (por ejemplo, la de pruebas). Si no están definidas, se usan los valores originales.
  define( 'DB_HOST', getenv('DB_HOST') ?: 'localhost' );   // Set database host
  define( 'DB_USER', getenv('DB_USER') ?: 'admin' );       // Set database user
  define( 'DB_PASS', getenv('DB_PASS') ?: 'root' );        // Set database password
  define( 'DB_NAME', getenv('DB_NAME') ?: 'oswa_inv' );    // Set database name

?>
