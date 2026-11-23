#!/usr/bin/env bash
# Prueba de humo del sistema completo a través de la puerta de entrada (nginx).
# Verifica que el sistema PHP, la API y la interfaz nueva funcionan juntos,
# con la sesión compartida y la protección CSRF.
#
# Uso: scripts/prueba-humo.sh [URL]   (por defecto http://localhost:8080)

set -euo pipefail

BASE="${1:-http://localhost:8080}"
COOKIES="$(mktemp)"
trap 'rm -f "$COOKIES"' EXIT
FALLAS=0

codigo() { curl -s -o /dev/null -w '%{http_code}' -b "$COOKIES" -c "$COOKIES" "$@"; }

verificar() {
  local descripcion="$1" esperado="$2" obtenido="$3"
  if [[ "$obtenido" == "$esperado" ]]; then
    echo "✅ $descripcion ($obtenido)"
  else
    echo "❌ $descripcion: se esperaba $esperado y se obtuvo $obtenido"
    FALLAS=$((FALLAS + 1))
  fi
}

echo "Prueba de humo contra $BASE"

# Sin sesión
verificar "La interfaz nueva sin sesión redirige al login" 302 "$(codigo "$BASE/productos/")"
verificar "La API sin sesión responde 401" 401 "$(codigo "$BASE/api/v2/productos")"
verificar "El volcado de la base no se puede descargar (H-14)" 404 "$(codigo "$BASE/oswa_inv.sql")"
verificar "La configuración interna no se puede descargar (H-14)" 404 "$(codigo "$BASE/includes/config.php")"
verificar "La verificación de sesión no es pública" 404 "$(codigo "$BASE/api_sesion.php")"

# Inicio de sesión en el sistema PHP
TOKEN_FORM="$(curl -s -b "$COOKIES" -c "$COOKIES" "$BASE/index.php" \
  | grep -o 'name="csrf_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//; s/"//')"
verificar "Inicio de sesión del sistema PHP" 302 "$(codigo \
  --data-urlencode username=admin --data-urlencode password=admin \
  --data-urlencode "csrf_token=$TOKEN_FORM" "$BASE/auth.php")"
codigo "$BASE/home.php" > /dev/null
TOKEN_API="$(awk '$6 == "XSRF-TOKEN" {print $7}' "$COOKIES")"

# Con sesión
verificar "La API con sesión responde" 200 "$(codigo "$BASE/api/v2/productos")"
verificar "La interfaz nueva con sesión carga" 200 "$(codigo "$BASE/productos/")"
verificar "product.php redirige a la interfaz nueva" 302 "$(codigo "$BASE/product.php")"
verificar "El sistema PHP sigue funcionando" 200 "$(codigo "$BASE/sales.php")"

# Una categoría creada desde el sistema PHP se usa desde la API: comparten la base.
TOKEN_FORM="$(curl -s -b "$COOKIES" -c "$COOKIES" "$BASE/categorie.php" \
  | grep -o 'name="csrf_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//; s/"//')"
codigo --data-urlencode "categorie-name=Humo $(date +%s)" --data-urlencode add_cat= \
  --data-urlencode "csrf_token=$TOKEN_FORM" "$BASE/categorie.php" > /dev/null
CATEGORIA="$(curl -s -b "$COOKIES" "$BASE/api/v2/categorias" | grep -o '"id":[0-9]*' | tail -1 | cut -d: -f2)"
verificar "La categoría creada en PHP se ve desde la API" 1 "$([[ -n "$CATEGORIA" ]] && echo 1 || echo 0)"

PRODUCTO="{\"nombre\":\"Prueba de humo $(date +%s)\",\"categoria_id\":${CATEGORIA:-0},\"stock\":3,\"precio_compra\":\"100\",\"precio_venta\":\"150\"}"
verificar "Una modificación sin token CSRF se rechaza" 403 "$(codigo -X POST \
  -H 'Content-Type: application/json' -d "$PRODUCTO" "$BASE/api/v2/productos")"
verificar "Una modificación con token CSRF se acepta" 201 "$(codigo -X POST \
  -H 'Content-Type: application/json' -H "X-CSRF-Token: $TOKEN_API" -d "$PRODUCTO" "$BASE/api/v2/productos")"

if [[ "$FALLAS" -gt 0 ]]; then
  echo "Fallaron $FALLAS verificaciones."
  exit 1
fi
echo "Todo funciona."
