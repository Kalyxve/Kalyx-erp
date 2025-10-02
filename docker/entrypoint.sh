#!/bin/sh
set -e

echo "🚀 Iniciando contenedor Laravel en Render..."

# Esperar a que la base de datos esté lista
echo "⏳ Esperando a que Postgres esté disponible..."
until php -r "
try {
    new PDO(getenv('DB_CONNECTION').':host='.parse_url(getenv('DATABASE_URL'), PHP_URL_HOST).';dbname='.ltrim(parse_url(getenv('DATABASE_URL'), PHP_URL_PATH), '/'),
            parse_url(getenv('DATABASE_URL'), PHP_URL_USER),
            parse_url(getenv('DATABASE_URL'), PHP_URL_PASS));
    exit(0);
} catch (Exception \$e) {
    exit(1);
}" >/dev/null 2>&1; do
  sleep 2
done

echo "✅ Base de datos disponible."

# Generar APP_KEY si no existe
if [ -z "$(php artisan key:generate --show 2>/dev/null)" ]; then
  echo "🔑 Generando APP_KEY..."
  php artisan key:generate --force
fi

# Ejecutar migraciones
echo "📂 Ejecutando migraciones..."
php artisan migrate --force

# Limpiar caches
echo "🧹 Limpiando caches de Laravel..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

echo "✅ App lista, levantando Nginx + PHP-FPM"

# Lanzar supervisord (mantiene Nginx y PHP-FPM corriendo)
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
