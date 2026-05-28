# Auditoría de Código — Plan de Mejoras
## Cohort Monitor

Documento generado el 9 de marzo de 2026 tras auditoría integral del código fuente.
Cada item incluye: ubicación, problema, solución propuesta y esfuerzo estimado.

**Leyenda de prioridad:**
- 🔴 **CRÍTICO** — Vulnerabilidad de seguridad activa, resolver de inmediato
- 🟠 **ALTO** — Problemas arquitectónicos o de rendimiento, resolver en sprint próximo
- 🟡 **MEDIO** — Mejoras de consistencia y mantenibilidad, resolver en backlog

---

## 🔴 CRÍTICOS

### C1 — Sin Protección CSRF en Formularios

| Campo | Detalle |
|-------|---------|
| **Ubicación** | Todos los controladores, 9+ formularios, `routes/web.php` |
| **Problema** | Ningún formulario genera ni valida tokens CSRF. No existe middleware CSRF. Un atacante puede hacer que usuarios autenticados ejecuten acciones no deseadas (crear cohortes, eliminar usuarios, cambiar contraseñas). |
| **Esfuerzo** | ~2-3 horas |

**Solución propuesta:**

1. Crear helpers `csrf_token()` y `csrf_field()` en `bootstrap/app.php`:
```php
function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}
```

2. Validar en `Router::dispatch()` para rutas POST:
```php
if ($method === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    if (!hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('CSRF token inválido');
    }
}
```

3. Agregar `<?= csrf_field() ?>` en cada `<form method="POST">`.

**Archivos a modificar:**
- `bootstrap/app.php`
- `app/Core/Router.php`
- Todas las vistas con formularios POST (~9 archivos en `app/Views/`)

---

### C2 — Logout vía GET (Vulnerable a CSRF por img/prefetch)

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `routes/web.php` línea 32 |
| **Problema** | Logout es GET — un `<img src="/logout">` cierra la sesión del usuario sin su consentimiento. |
| **Esfuerzo** | ~15 minutos |

**Solución propuesta:**

```php
// routes/web.php — cambiar de GET a POST
$router->post('/logout', [AuthController::class, 'logout'], 'auth.logout');
```

```php
// layouts/app.php — reemplazar <a> por formulario
<form method="POST" action="/logout" class="d-inline">
    <?= csrf_field() ?>
    <button type="submit" class="nav-link text-danger btn btn-link">
        <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
    </button>
</form>
```

**Archivos a modificar:**
- `routes/web.php`
- `app/Views/layouts/app.php` (sidebar logout link)

---

### C3 — Sin Configuración de Seguridad en Cookies de Sesión

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `bootstrap/app.php` — solo `session_name('cohort_session')` |
| **Problema** | No se configuran `Secure`, `HttpOnly` ni `SameSite` para la cookie de sesión. Esto permite captura de cookies por XSS o envío cross-site. |
| **Esfuerzo** | ~10 minutos |

**Solución propuesta:**

```php
// bootstrap/app.php — agregar ANTES de session_start()
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly'  => true,
    'samesite'  => 'Lax',
]);
```

**Archivos a modificar:**
- `bootstrap/app.php`

---

### C4 — Sin Rate Limiting en Login

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Services/AuthService.php` — método `attempt()` |
| **Problema** | Sin protección contra fuerza bruta, sin logging de intentos fallidos, sin bloqueo de cuenta. |
| **Esfuerzo** | ~1-2 horas |

**Solución propuesta:**

```php
public function attempt(string $email, string $password): ?array
{
    $key = 'login_attempts_' . md5($email);
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'last' => 0];

    if ($attempts['count'] >= 5 && (time() - $attempts['last']) < 900) {
        throw new \RuntimeException('Demasiados intentos. Espere 15 minutos.');
    }

    $user = $this->userRepo->findByEmail($email);
    if (!$user || !password_verify($password, $user['password'])) {
        $attempts['count']++;
        $attempts['last'] = time();
        $_SESSION[$key] = $attempts;
        return null;
    }

    unset($_SESSION[$key]);
    return $user;
}
```

**Archivos a modificar:**
- `app/Services/AuthService.php`

---

### C5 — `updatePartial()` sin Whitelist en Capa de Repositorio

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Repositories/CohortRepository.php` — método `updatePartial()` |
| **Problema** | `foreach ($data as $field => $value)` construye SQL con nombres de campo directos. Existe whitelist en `Auth::filterEditableCohortData()` a nivel de controller, pero falta defense-in-depth en el repositorio. |
| **Esfuerzo** | ~20 minutos |

**Solución propuesta:**

```php
private const ALLOWED_FIELDS = [
    'name', 'type', 'area', 'start_date', 'end_date', 'schedule',
    'status', 'target', 'enrolled', 'b2b', 'b2c', 'total_admissions',
    'notes', 'coach', 'modality', 'color_code',
];

public function updatePartial(int $id, array $data): bool
{
    $data = array_intersect_key($data, array_flip(self::ALLOWED_FIELDS));
    if (empty($data)) return false;
    // ... resto de la lógica existente
}
```

**Archivos a modificar:**
- `app/Repositories/CohortRepository.php`

---

## 🟠 ALTA PRIORIDAD

### H1 — UserService Muta `$_SESSION` Directamente

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Services/UserService.php` líneas ~235-236 |
| **Problema** | `$_SESSION['user']['full_name'] = $data['full_name'];` — el Service accede al superglobal HTTP, violando separación de capas. |
| **Esfuerzo** | ~30 minutos |

**Solución:** Retornar datos actualizados al controller y que este actualice la sesión vía `Auth::refreshSession()`.

**Archivos a modificar:**
- `app/Services/UserService.php`
- `app/Controllers/AccountController.php` (o el controller que llame a updateProfile)
- `app/Core/Auth.php` (agregar `refreshSession()` si no existe)

---

### H2 — ReportService Escribe Headers HTTP Directamente

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Services/ReportService.php` líneas ~187-189, ~207-208 |
| **Problema** | `header('Content-Type: ...')` y `header('Content-Disposition: ...')` directamente en el service. Los Services no deben producir output HTTP. |
| **Esfuerzo** | ~45 minutos |

**Solución:** El service debe retornar un array/objeto con metadatos (`filename`, `content_type`, `stream`). El controller envía headers y body.

**Archivos a modificar:**
- `app/Services/ReportService.php`
- `app/Controllers/ReportController.php`

---

### H3 — DashboardService Carga TODAS las Cohortes para Mostrar 5

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Services/DashboardService.php` línea ~51 |
| **Problema** | `$this->cohortRepo->findAll()` + `array_slice($recentCohorts, 0, 5)` — carga 112+ registros para usar solo 5. |
| **Esfuerzo** | ~20 minutos |

**Solución:** Crear `CohortRepository::findRecent(int $limit = 5)`:
```php
public function findRecent(int $limit = 5): array
{
    return $this->db->query(
        'SELECT * FROM cohorts ORDER BY created_at DESC LIMIT :limit',
        ['limit' => $limit]
    );
}
```

**Archivos a modificar:**
- `app/Repositories/CohortRepository.php`
- `app/Services/DashboardService.php`

---

### H4 — UserService N+1: Carga TODOS los Usuarios para Contar Admins

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Services/UserService.php` líneas ~121, ~161 |
| **Problema** | `array_filter($this->userRepo->findAll(), fn($u) => ...)` carga todos los usuarios para contar admins activos. |
| **Esfuerzo** | ~15 minutos |

**Solución:** Crear `UserRepository::countActiveByRole(string $role): int`:
```php
public function countActiveByRole(string $role): int
{
    $rows = $this->db->query(
        'SELECT COUNT(*) as cnt FROM users WHERE role = :role AND is_active = 1',
        ['role' => $role]
    );
    return (int) ($rows[0]['cnt'] ?? 0);
}
```

**Archivos a modificar:**
- `app/Repositories/UserRepository.php`
- `app/Services/UserService.php`

---

### H5 — MarketingService N+1 para Búsqueda de Stage

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Services/MarketingService.php` líneas ~56-62 |
| **Problema** | Carga TODAS las stages de un cohort para buscar un registro específico. |
| **Esfuerzo** | ~15 minutos |

**Solución:** Crear `MarketingStageRepository::findByCohortAndStage(int $cohortId, string $stageName)`.

**Archivos a modificar:**
- `app/Repositories/MarketingStageRepository.php`
- `app/Services/MarketingService.php`

---

### H6 — CohortImportService Carga TODAS las Cohortes para Detección de Duplicados

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Services/CohortImportService.php` línea ~392 |
| **Problema** | `SELECT name, start_date FROM cohorts` — carga toda la tabla para comparar duplicados. |
| **Esfuerzo** | ~30 minutos |

**Solución:** Consulta dirigida por fila: `SELECT 1 FROM cohorts WHERE name = ? AND start_date = ? LIMIT 1`, o usar `INSERT ... ON DUPLICATE KEY`.

**Archivos a modificar:**
- `app/Services/CohortImportService.php`

---

### H7 — Sin Soporte de Paginación en Ningún Repositorio

| Campo | Detalle |
|-------|---------|
| **Ubicación** | Todos los repositorios — `findAll()` sin `LIMIT`/`OFFSET` |
| **Problema** | No existe paginación. Cada `findAll()` retorna todos los registros. Prerrequisito para Phase 2 del PRD (REST API). |
| **Esfuerzo** | ~2-3 horas (todos los repos) |

**Solución:** Agregar en cada repo que lo necesite:
```php
public function findPaginated(int $page, int $perPage, array $filters = []): array { ... }
public function countFiltered(array $filters = []): int { ... }
```

**Archivos a modificar:**
- `app/Repositories/CohortRepository.php` (prioridad)
- `app/Repositories/UserRepository.php`
- `app/Repositories/CommentRepository.php`

---

### H8 — AlertService Instancia AuditRepository Internamente

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Services/AlertService.php` línea ~32 |
| **Problema** | `$auditRepo = new AuditRepository();` — viola el patrón de inyección por constructor usado en el resto de la app. |
| **Esfuerzo** | ~10 minutos |

**Solución:** Inyección por constructor:
```php
public function __construct(private AuditRepository $auditRepo) {}
```

**Archivos a modificar:**
- `app/Services/AlertService.php`

---

### H9 — Contraseña Temporal Visible en Flash Message HTML

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Controllers/UserController.php` — `resetPassword()` |
| **Problema** | `Auth::flash('success', "... <code>{$newPassword}</code>")` — contraseña visible en HTML source, cacheable por navegador. |
| **Esfuerzo** | ~30 minutos |

**Solución:** Pasar la contraseña como variable separada a la vista con campo `type="password"` revealable, o enviar por email sin mostrar en UI.

**Archivos a modificar:**
- `app/Controllers/UserController.php`
- `app/Views/users/index.php` (modal o sección para mostrar contraseña de forma segura)

---

## 🟡 MEDIA / BAJA PRIORIDAD

### M1 — Formatos de Respuesta de Error Inconsistentes

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Controllers/CohortController.php` — varios métodos |
| **Problema** | Algunos errores usan `json_encode() + echo`, otros usan `view()` + redirect. |

**Solución:** Estandarizar: errores AJAX → JSON; errores de formulario → redirect con flash message.

---

### M2 — Escaping Inconsistente: `e()` vs `htmlspecialchars()`

| Campo | Detalle |
|-------|---------|
| **Ubicación** | Múltiples archivos en `app/Views/` |
| **Problema** | Algunas vistas usan `e()` (helper de `bootstrap/app.php`) y otras usan `htmlspecialchars()` directamente. |

**Solución:** Estandarizar todo en `e()`.

---

### M3 — IDs HTML Dinámicos sin Escapar (Marketing)

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Views/marketing/show.php` |
| **Problema** | `id="modal-<?= $stage['stage_name'] ?>"` — si `stage_name` contiene caracteres especiales, rompe el HTML. |

**Solución:** `id="modal-<?= e($stage['stage_name']) ?>"`.

---

### M4 — Subqueries EXISTS Ineficientes en ReportRepository

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Repositories/ReportRepository.php` líneas ~27-29, ~53-55 |
| **Problema** | EXISTS subquery ejecuta por cada fila. |

**Solución:** Reemplazar con LEFT JOIN + COUNT.

---

### M5 — MarketingStageRepository: 4 INSERTs Individuales en Loop

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Repositories/MarketingStageRepository.php` — `ensureStagesForCohort()` |
| **Problema** | 4 queries INSERT IGNORE separadas, una por stage. |

**Solución:** Batch insert: `INSERT INTO marketing_stages (...) VALUES (...),(...),(...),(...)`.

---

### M6 — Métodos de Repositorio Faltantes

| Repositorio | Métodos sugeridos |
|-------------|-------------------|
| `CohortRepository` | `findRecent()`, `findByStatus()`, `findPaginated()` |
| `UserRepository` | `countActiveByRole()`, `findByRole()` |
| `MarketingStageRepository` | `findByCohortAndStage()`, `deleteForCohort()` |
| `CommentRepository` | `delete()`, `update()`, `findById()` |
| `AuditRepository` | `findByUser()`, `purgeOlderThan()` |

---

### M7 — `$_SERVER['REMOTE_ADDR']` sin Validar en AuditRepository

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Repositories/AuditRepository.php` línea ~24 |
| **Problema** | Detrás de proxy (Railway), no se valida `X-Forwarded-For`. |

**Solución:** Usar `$_SERVER['HTTP_X_FORWARDED_FOR']` con `filter_var(..., FILTER_VALIDATE_IP)`.

---

### M8 — schema.sql Incompleto

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `database/schema.sql` — solo tablas `cohorts` + `students` |
| **Problema** | Faltan 5 tablas (users, sessions, marketing_stages, cohort_comments, audit_log). |

**Solución:** Generar schema.sql completo con las 7 tablas, o consolidar todas las migraciones.

---

### M9 — Sin try-catch en Algunos Controllers

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `DashboardController`, `AlertController`, `CoachCalendarController` |
| **Problema** | No tienen try-catch, a diferencia de `CohortController` y `UserController`. |

**Solución:** Agregar try-catch con logging consistente.

---

### M10 — CohortImportService Accede a `$_FILES` Directamente

| Campo | Detalle |
|-------|---------|
| **Ubicación** | `app/Services/CohortImportService.php` línea ~78 |
| **Problema** | El service accede al superglobal `$_FILES` en vez de recibir la ruta como parámetro. |

**Solución:** El controller pasa `$_FILES['file']['tmp_name']` como argumento al service.

---

## Plan de Ejecución por Sprints

| Sprint | Items | Esfuerzo Est. | Descripción |
|--------|-------|---------------|-------------|
| **Sprint 1 — Seguridad** | C1, C2, C3, C4, C5 | ~4-5 horas | CSRF tokens, cookies seguras, rate limiting, whitelist en repo |
| **Sprint 2 — Arquitectura** | H1, H2, H8, H9, M10 | ~2 horas | Eliminar accesos a superglobals desde services, DI, password seguro |
| **Sprint 3 — Performance** | H3, H4, H5, H6, M4, M5 | ~2 horas | Eliminar N+1 queries, optimizar subqueries y batch inserts |
| **Sprint 4 — Phase 2 Prep** | H7, M6, M8 | ~3-4 horas | Paginación, métodos de repositorio, schema completo |
| **Sprint 5 — Consistencia** | M1, M2, M3, M7, M9 | ~2 horas | Estandarizar errores, escaping, try-catch, IP validation |

---

## Alineación con PRD Roadmap

- **Phase 2** (REST API, paginación, búsqueda): H7 y M6 son **prerrequisitos directos**.
- **Phase 3** (métricas, notificaciones): La paginación (H7) y los repo methods (M6) facilitan dashboards con datos agregados.
- **Seguridad**: C1-C5 deben resolverse **antes** de exponer REST API en Phase 2.
