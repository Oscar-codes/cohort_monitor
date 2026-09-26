# Logs y diagnóstico seguro — CM-SEC-004

> Documento operativo: cómo la app reporta errores y cómo responde
> ante fallos de la base de datos o del flujo de auth. Define el contrato
> de "no filtrar DSN ni mensajes crudos al usuario o al log".

## Filosofía

- **Las respuestas HTTP nunca deben contener DSN, SQL, host:port ni
  stack traces.** Aun los administradores ven versiones resumidas con
  un *fingerprint*; los detalles van sólo al log del servidor.
- **Los logs en disco no deben contener identificadores de usuario en
  plano (username, email, IP completa)**. Para IPs y nombres se
  aplica `SafeLog::redact()` que los sustituye por un prefijo `sha256`
  determinista.
- **Los logs sí contienen la categoría (`auth.login`), la clase de la
  excepción y un fingerprint** para que un desarrollador pueda
  correlacionar lo que el usuario ve con el evento del servidor.

## Cambios aplicados (CM-SEC-004)

| Archivo | Cambio |
|---|---|
| `app/Core/SafeLog.php` (nuevo) | `SafeLog::record(category, throwable)` para logueos privados, `SafeLog::fingerprint(throwable, category)` para referencias, `SafeLog::redact(string)` para identificadores. |
| `app/Core/Database.php` | La excepción pública ya no incluye DSN ni PDO message; `SafeLog::record('database.connection', $e)` emite sólo un fingerprint en log. |
| `app/Controllers/AdminController.php` | `health()` captura `Throwable` y muestra `Ref de diagnostico: <fingerprint>` en lugar del mensaje del driver. |
| `app/Services/AuthService.php` | Las 6 llamadas a `error_log(... . $e->getMessage())` pasan por `SafeLog::record()`. IPs se redactan con `SafeLog::redact()`. |
| `app/Core/Controller.php` | `logException()` se complementa con `SafeLog::record()` para añadir el fingerprint al log. |
| `bootstrap/app.php` | En producción, `error_reporting=E_ALL` + `display_errors=0` + `html_errors=0`. Captura errores sin filtrarlos al cliente. |

## Tabla de garantía

| Riesgo | Antes | Ahora |
|---|---|---|
| Exception de PDO con DSN al cliente | `[dsn=mysql:host=…;port=…;dbname=…;charset=utf8mb4]` | `Database connection failed.` + fingerprint en log |
| Mensaje crudo de driver en `/admin/health` | `SQLSTATE[HY000] [1045] Access denied for user 'X'@'Y' (using password: YES)` | `Ref de diagnostico: a3f29bc1` (8 chars de sha256) |
| IP plana en logs | `ip=187.45.66.10` | `ip=redact:8c0a4f23` (sha256 prefix) |
| Mensaje crudo de PDO en logs | `SQLSTATE[42S02] Base table … not found` | `:: fp=a3f29bc1 :: (no message)` |
| `display_errors=1` en producción por error de configuración | Sí (via APP_DEBUG heredado) | No — se fuerza `display_errors=0` siempre que APP_DEBUG sea falso, independiente de la variable existente |

## Verificación manual

1. **Forzar fallo de conexión**. Cambiar la contraseña en `config/database.php`. Cargar `/admin/health`. La respuesta debe ser `Ref de diagnostico: xxxxxxxx`, no `Access denied`. El log del servidor debe incluir `:: fp=xxxxxxxx :: PDOException :: SQLSTATE[HY000] [1045]…`.
2. **Verificar IP redactada en logs**. Iniciar sesión y fallar 1 vez. `error_log` debe contener `ip=redact:…` y no la IP real.
3. **Comprobar `display_errors=0` en producción**. Con `APP_DEBUG=false`, `php -r 'require "bootstrap/app.php"; var_dump(ini_get("display_errors"))'` debe devolver `''` o `'0'`.
4. **Forzar un login_fail**. `error_log` debe contener `[auth] login failed reason=invalid_credentials ip=redact:…` y NO el username/email/contraseña.

## Limitaciones conocidas

- `error_reporting=E_ALL` activa la captura de notices/deprecations; el log puede crecer rápido. Se recomienda un rotador externo (logrotate o Supervisord) en producción.
- `SafeLog::redact()` usa sha256 con prefijo de 8 chars. Es estable pero **no es HMAC**: dos operadores con el mismo input obtendrán el mismo redact, así que un dump filtrado permite correlacionar. Si se requiere opacidad entre despliegues, considere HMAC con clave rotada.
- En producción, `error_log()` por defecto escribe a `error_log` del SAPI (Apache / php-fpm log). Si se quiere centralizar, basta con añadir `ini_set('error_log', '/var/log/cohort/app.log')` en `bootstrap/app.php`.

## Próximos pasos sugeridos

- Auditoría de los demás servicios para reemplazar `error_log('[worker] ... ' . $e->getMessage())` por `SafeLog::record('worker', $e)`.
- Considerar añadir `register_shutdown_function()` para atrapar fatal errors y persistir el fingerprint al log de aplicación.
