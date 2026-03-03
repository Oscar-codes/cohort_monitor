#!/bin/bash
# Script de inicio para Railway
# Genera .env desde las variables de entorno inyectadas por Railway

ENV_FILE="/var/www/html/.env"

echo "APP_ENV=${APP_ENV:-production}" > "$ENV_FILE"
echo "APP_DEBUG=${APP_DEBUG:-false}" >> "$ENV_FILE"
echo "APP_URL=${APP_URL:-http://localhost}" >> "$ENV_FILE"
echo "" >> "$ENV_FILE"
echo "DB_HOST=${DB_HOST:-127.0.0.1}" >> "$ENV_FILE"
echo "DB_PORT=${DB_PORT:-3306}" >> "$ENV_FILE"
echo "DB_DATABASE=${DB_DATABASE:-cohort_monitor}" >> "$ENV_FILE"
echo "DB_USERNAME=${DB_USERNAME:-root}" >> "$ENV_FILE"
echo "DB_PASSWORD=${DB_PASSWORD:-}" >> "$ENV_FILE"
echo "DB_CHARSET=${DB_CHARSET:-utf8mb4}" >> "$ENV_FILE"

echo "=== .env generated ==="
cat "$ENV_FILE" | sed 's/DB_PASSWORD=.*/DB_PASSWORD=****/'
echo "======================"

# Iniciar el servidor PHP
exec php -S 0.0.0.0:${PORT:-8000} -t public public/index.php
