#!/bin/sh
set -e

echo "🚀 Iniciando contenedor Laravel en Render..."

# 1) Renderiza el server block de Nginx con el PORT dinámico
#    (necesario o Nginx no sabrá en qué puerto escuchar)
envsubst '$PORT' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# 2) Compila caches (si falla, no abortar el arranque)
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true

# 3) Migraciones con reintentos limitados (evita bucles infinitos)
echo "📂 Ejecutando migraciones..."
ATTEMPTS=0
until php artisan migrate --force; do
  ATTEMPTS=$((ATTEMPTS+1))
  if [ $ATTEMPTS -ge 5 ]; then
    echo "⚠️  Migraciones no aplicaron tras $ATTEMPTS intentos. Continuando para no bloquear el deploy."
    break
  fi
  echo "⏳ Reintentando migraciones en 5s (intento $ATTEMPTS/5)..."
  sleep 5
done

echo "✅ App lista, levantando Nginx + PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
