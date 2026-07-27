#!/bin/bash

# ─── Túnel SSH hacia la base de datos remota ────────────────
if [ "${DB_SSH_ENABLED}" = "true" ]; then
  echo "=== Abriendo túnel SSH hacia ${DB_SSH_HOST} ==="

  # Guardar la llave privada cifrada en un archivo temporal
  echo "${DB_SSH_PRIVATE_KEY_CONTENT}" > /tmp/ssh_key_encrypted.pem

  # Descifrarla usando la passphrase (ya viene en las variables de entorno)
  openssl rsa -in /tmp/ssh_key_encrypted.pem \
    -out /tmp/ssh_key_decrypted.pem \
    -passin pass:"${DB_SSH_PRIVATE_KEY_PASSPHRASE}"

  chmod 600 /tmp/ssh_key_decrypted.pem

  # Puerto local fijo (si DB_SSH_LOCAL_PORT es 0, usamos 3306 por defecto)
  LOCAL_PORT="${DB_SSH_LOCAL_PORT}"
  if [ "${LOCAL_PORT}" = "0" ] || [ -z "${LOCAL_PORT}" ]; then
    LOCAL_PORT=3306
  fi

  # Abrir el túnel en segundo plano
  ssh -i /tmp/ssh_key_decrypted.pem \
      -p "${DB_SSH_PORT}" \
      -N \
      -L "${LOCAL_PORT}:${DB_SSH_REMOTE_HOST}:${DB_SSH_REMOTE_PORT}" \
      -o StrictHostKeyChecking=no \
      -o ServerAliveInterval=30 \
      -o ExitOnForwardFailure=yes \
      "${DB_SSH_USER}@${DB_SSH_HOST}" &

  SSH_PID=$!
  echo "Túnel SSH abierto (PID ${SSH_PID}), esperando a que esté listo..."
  sleep 3

  # Confirmar que el túnel sigue vivo
  if ! kill -0 $SSH_PID 2>/dev/null; then
    echo "ERROR: el túnel SSH no se pudo establecer."
  else
    echo "Túnel SSH activo en 127.0.0.1:${LOCAL_PORT}"
  fi
fi

# ─── Generar .env desde las variables de entorno de Railway ─
ENV_FILE="/var/www/html/.env"

FINAL_DB_HOST="127.0.0.1"
FINAL_DB_PORT="${LOCAL_PORT:-${DB_PORT:-${MYSQLPORT:-3306}}}"
FINAL_DB_DATABASE="${DB_DATABASE:-${DB_NAME:-${MYSQLDATABASE:-railway}}}"
FINAL_DB_USERNAME="${DB_USERNAME:-${DB_USER:-${MYSQLUSER:-root}}}"
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
echo "========================"

# Iniciar el servidor PHP
exec php -S 0.0.0.0:${PORT:-8000} -t public public/index.php