#!/bin/bash
# Script de inicio para Railway
# Genera .env desde las variables de entorno inyectadas por Railway

ENV_FILE="/var/www/html/.env"

# Railway inyecta MYSQLHOST, MYSQLPORT, etc. directamente
# También podría tener DB_HOST si se configuró manualmente
# Priorizamos: DB_HOST > MYSQLHOST > default
FINAL_DB_HOST="${DB_HOST:-${MYSQLHOST:-127.0.0.1}}"
FINAL_DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
FINAL_DB_DATABASE="${DB_DATABASE:-${MYSQLDATABASE:-railway}}"
FINAL_DB_USERNAME="${DB_USERNAME:-${MYSQLUSER:-root}}"
FINAL_DB_PASSWORD="${DB_PASSWORD:-${MYSQLPASSWORD:-}}"

echo "APP_ENV=${APP_ENV:-production}" > "$ENV_FILE"
echo "APP_DEBUG=${APP_DEBUG:-true}" >> "$ENV_FILE"
echo "APP_URL=${APP_URL:-http://localhost}" >> "$ENV_FILE"
echo "" >> "$ENV_FILE"
echo "DB_HOST=${FINAL_DB_HOST}" >> "$ENV_FILE"
echo "DB_PORT=${FINAL_DB_PORT}" >> "$ENV_FILE"
echo "DB_DATABASE=${FINAL_DB_DATABASE}" >> "$ENV_FILE"
echo "DB_USERNAME=${FINAL_DB_USERNAME}" >> "$ENV_FILE"
echo "DB_PASSWORD=${FINAL_DB_PASSWORD}" >> "$ENV_FILE"
echo "DB_CHARSET=${DB_CHARSET:-utf8mb4}" >> "$ENV_FILE"

echo "=== .env generated ==="
echo "DB_HOST=${FINAL_DB_HOST}"
echo "DB_PORT=${FINAL_DB_PORT}"
echo "DB_DATABASE=${FINAL_DB_DATABASE}"
echo "DB_USERNAME=${FINAL_DB_USERNAME}"
echo "DB_PASSWORD=****"
echo "=== Raw Railway vars ==="
echo "MYSQLHOST=${MYSQLHOST:-NOT SET}"
echo "MYSQLPORT=${MYSQLPORT:-NOT SET}"
echo "MYSQLDATABASE=${MYSQLDATABASE:-NOT SET}"
echo "MYSQLUSER=${MYSQLUSER:-NOT SET}"
echo "MYSQL_URL=${MYSQL_URL:-NOT SET}"
echo "DB_HOST env=${DB_HOST:-NOT SET}"
echo "========================"

# Iniciar el servidor PHP
exec php -S 0.0.0.0:${PORT:-8000} -t public public/index.php
