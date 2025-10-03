#!/bin/sh
set -e

echo "🚀 Iniciando contenedor Laravel en Render..."

# 1) Nginx escucha en el puerto dinámico
if command -v envsubst >/dev/null 2>&1; then
  envsubst '$PORT' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
else
  sed "s|\${PORT}|${PORT}|g" /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
fi

# 2) Caches (no bloqueantes)
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true

# 3) Migraciones con reintentos limitados
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
