# Cookies y sesiones — verificación CM-SEC-002

> Documento vivo: cómo se configura la sesión en el backend y qué trazas
> manuales confirman el comportamiento. Los pasos operativos sirven tanto
> para una verificación manual previa a un despliegue como para una
> auditoría posterior a un incidente.

## Variables de entorno relevantes

| Variable | Default | Descripción |
|---|---|---|
| `APP_DEBUG` | `false` en producción | Si es `true`, `secure` se desactiva para entornos `http://localhost`. |
| `APP_URL` | `http://localhost:8000` | Si empieza por `https://`, se activa el flag `Secure` automáticamente. |
| `SESSION_LIFETIME` | `7200` (2h) | Tiempo máximo de inactividad en segundos. Tras este periodo sin requests, la sesión se destruye. |
| `SESSION_ABSOLUTE_LIFETIME` | `28800` (8h) | Tiempo máximo absoluto desde el login, independiente de la actividad. |
| `SESSION_COOKIE_SAMESITE` | `Lax` | Política de envío cross-site (`Lax`, `Strict`, `None`). |
| `SESSION_COOKIE_SECURE` | auto | Override explícito (`1`/`true`/`0`/`false`). |
| `SESSION_COOKIE_HTTPONLY` | `1` | Flag HttpOnly del cookie de sesión. |

`SessionConfig::apply()` se invoca automáticamente en `Auth::boot()`
antes de `session_start()`. `SessionConfig::expireCookie()` se usa desde
`Auth::logout()` y desde la rama de `enforcePolicy()` cuando la sesión
expiró, de modo que el cookie expira con la misma configuración que se
emitió originalmente.

## Política aplicada al boot

```text
Auth::boot()
  └─ SessionConfig::apply()
       ├─ session_set_cookie_params(lifetime, path, domain, secure, httponly, samesite)
       ├─ ini_set('session.gc_maxlifetime', absolute_lifetime)
       ├─ ini_set('session.use_strict_mode', '1')
       └─ ini_set('session.use_only_cookies', '1')
  └─ session_name('cohort_session')
  └─ session_start()
  └─ enforcePolicy()
       ├─ idle timeout:  _last_activity  > SESSION_LIFETIME             → destroy
       ├─ absolute cap:  (now - _login_at) > SESSION_ABSOLUTE_LIFETIME  → destroy
       ├─ fingerprint:   _fingerprint != UA+IP hash                    → destroy
       └─ refresh:       _last_activity = now
```

`fingerprint()` usa `sha256(HTTP_USER_AGENT . clientIp())`. El `clientIp`
intenta en orden `CF-Connecting-IP`, `X-Forwarded-For`, `X-Real-IP`,
`REMOTE_ADDR`. Cuando hay CGN/proxy compartido entre dispositivos puede
romperse la sesión — comportamiento deseado para reducir riesgo de
secuestro de cookie. En despliegues donde esto sea un problema, se puede
extender el cálculo para considerar solo los primeros tres octetos de IPv4
o el prefijo IPv6/64.

## Pasos de verificación manual

1. **Cookie emite con HttpOnly + SameSite**.
   - Inicia sesión (POST /login) con curl y guarda cookies (`-c cookies.txt`).
   - Ejecuta `curl -b cookies.txt -i http://localhost:8000/account` y revisa la respuesta.
   - Inspecciona el header `Set-Cookie`: debe incluir `HttpOnly`, `SameSite=Lax`, `Path=/`, `Expires`/`Max-Age` derivado de `SESSION_LIFETIME` y (en producción) `Secure`.
2. **Cookie no envía Secure en `http://localhost`**.
   - En local el flag `Secure` debe estar **ausente** (si faltara, los navegadores persistentes lo respetarían sólo en HTTPS).
3. **Logout elimina cookie correctamente**.
   - Realiza `curl -b cookies.txt -X POST http://localhost:8000/logout -i`. La respuesta debe llegar con `Set-Cookie: cohort_session=…; expires=Thu, 01 Jan 1970…` y los mismos atributos que la original.
4. **Idle timeout se respeta**.
   - Inicia sesión y replica la cookie.
   - Espera `SESSION_LIFETIME + 10`s sin actividad; cualquier request debe responder `302 /login`.
5. **Absolute cap se respeta**.
   - Inicia sesión y replica la cookie.
   - Aunque navegues activamente, espera `SESSION_ABSOLUTE_LIFETIME + 10`s — la sesión debe haber sido destruida y exigir nuevo login.
6. **Cambio de User-Agent o IP invalida la sesión**.
   - Inicia sesión desde un browser.
   - Copia el cookie de sesión a otro contexto que cambie User-Agent (por ejemplo `curl -H "User-Agent: Otro"`). La siguiente respuesta debe `302 /login` y un nuevo cookie anonimo.
7. **Session fixation queda mitigada por `session_regenerate_id(true)`**.
   - Inicia sesión, captura el `cohort_session` actual.
   - Re-login con el mismo usuario: la cookie debe cambiar (nuevo `Set-Cookie`), invalidando la primera.
8. **Login no permite conservar IP/UA anteriores a la regeneración**.
   - Durante `Auth::login()` se regenera el id, se vuelve a registrar `fingerprint` y `last_activity`. Ya cubierto por `enforcePolicy()` en la siguiente request.
9. **Strict mode / use_only_cookies está activo**.
   - `php -r 'echo ini_get("session.use_strict_mode");'` debe devolver `1`.
   - `php -r 'echo ini_get("session.use_only_cookies");'` debe devolver `1`.

## Diagnóstico rápido

```powershell
php -r 'require "bootstrap/app.php"; echo json_encode(SessionConfig::cookieParams(Auth::SESSION_NAME));'
```

Devuelve los parámetros que `session_set_cookie_params` y la cookie de
logout están usando — útil para verificar el `.env` sin gastar requests.

## Limitaciones explícitas

- **Revocación multi-sesión** aún no está cableada a la tabla `sessions`
  definida en `database/schema.sql`. El backend usa sesiones nativas de
  PHP, lo que impide revocar centralmente una sesión de otro dispositivo.
  Para habilitarla sin migración invasiva, se propone una variante con
  `SessionHandlerInterface` respaldada por la tabla `sessions` (siguiente
  iteración, fuera de CM-SEC-002).
- **Rate-limit de login y migración de hashes** siguen en CM-SEC-003.
- **Sanitización de logs** siguen en CM-SEC-004.
