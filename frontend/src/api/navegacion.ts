/** Página de inicio de sesión del sistema PHP, que sigue siendo la puerta de entrada. */
export const URL_LOGIN = "/index.php";

/** Página principal del sistema PHP. */
export const URL_SISTEMA = "/home.php";

export function irAlLogin(): void {
  window.location.assign(URL_LOGIN);
}
