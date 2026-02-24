# Changelog
## Cohort Monitor

Todos los cambios notables de este proyecto se documentan en este archivo.

---

## [1.5.0] — 2026-02-23

### 👤 Módulo de Cuenta de Usuario

Se implementó un sistema completo de gestión de cuenta de usuario con self-service para todos los usuarios y funcionalidades avanzadas de administración.

#### Mi Cuenta (Self-Service — Todos los roles)
- **Vista de perfil** (`/account`): Tarjeta resumen con avatar de iniciales, rol, estado, último acceso, fecha de registro
- **Editar perfil**: Formulario para actualizar nombre completo y email (username no editable)
- **Cambiar contraseña**: Formulario con validación de contraseña actual, nueva contraseña (mín. 8 caracteres) y confirmación
- **Actualización de sesión**: Los cambios de perfil se reflejan inmediatamente en la sesión activa

#### Administración de Usuarios (Admin — Mejoras)
- **Toggle activar/desactivar**: Botón para cambiar estado activo/inactivo de usuarios con un clic
- **Restablecer contraseña**: Genera contraseña aleatoria de 12 caracteres y la muestra al admin
- **Protección último admin**: No se puede eliminar ni desactivar al último administrador activo del sistema
- **Columna "Último Acceso"**: Nueva columna en la tabla de usuarios con fecha/hora del último login
- **Acciones expandidas**: Botones de toggle status, reset password y delete por usuario

#### Archivos Nuevos
| Archivo | Descripción |
|---------|-------------|
| `app/Controllers/AccountController.php` | Controlador self-service: `profile()`, `updateProfile()`, `changePassword()` |
| `app/Views/account/profile.php` | Vista de perfil con tarjeta resumen + formularios de edición |

#### Archivos Modificados
| Archivo | Cambios |
|---------|---------|
| `app/Services/UserService.php` | +5 métodos: `updateProfile()`, `changePassword()`, `toggleStatus()`, `resetPassword()`, `generateRandomPassword()`. Protección último admin |
| `app/Controllers/UserController.php` | +2 métodos: `toggleStatus()`, `resetPassword()` |
| `app/Views/users/index.php` | +columna "Último Acceso", botones toggle/reset, flash HTML para contraseñas |
| `app/Views/partials/sidebar.php` | +enlace "Mi Cuenta" visible para todos los roles |
| `routes/web.php` | +5 rutas: `GET/POST /account`, `POST /account/password`, `POST /users/{id}/toggle-status`, `POST /users/{id}/reset-password` |

---

## [1.4.0] — 2026-02-23

### 📥 Módulo de Importación Masiva de Cohortes

Se implementó un sistema completo de importación bulk desde archivos Excel/CSV.

#### Funcionalidades
- **Carga de archivos**: Drag & drop + selector de archivos para `.xlsx`, `.xls`, `.csv`
- **Validación por fila**: Verifica campos obligatorios, formatos de fecha, valores de ENUM, rangos numéricos
- **Detección de duplicados**: Compara por nombre + fecha de inicio contra cohortes existentes
- **Bulk insert con transacciones**: Inserta todas las filas validadas o revierte ante error fatal
- **Plantilla descargable**: Genera archivo Excel con headers, validaciones de datos y ejemplos

#### Archivos Nuevos
| Archivo | Descripción |
|---------|-------------|
| `app/Controllers/ImportCohortController.php` | `showForm()`, `handleImport()`, `downloadTemplate()` |
| `app/Services/CohortImportService.php` | Lectura de archivos, validación, normalización, bulk insert, generación de plantilla |
| `app/Views/cohorts/import.php` | Zona de upload drag & drop, instrucciones, resumen de resultados, tabla de errores |

#### Rutas Añadidas
- `GET /cohorts/import` — Formulario de importación
- `POST /cohorts/import` — Procesar archivo
- `GET /cohorts/import/template` — Descargar plantilla Excel

---

## [1.3.0] — 2026-02-23

### 📊 Mejoras en Tabla de Cohortes + Reportes

#### Nuevas Columnas en Tabla de Cohortes
- **Columna "Proyecto"**: Muestra `related_project` después de "Tipo", oculta en móvil (`d-none d-md-table-cell`)
- **Columna "Formación"**: Campo calculado después de "Admisiones":
  - B2B > 0 && B2C > 0 → **Mixta** (badge warning)
  - Solo B2B > 0 → **B2B** (badge info)
  - Solo B2C > 0 → **B2C** (badge primary)
  - Ambos 0 → "—"

#### Módulo de Reportes (v1.2.1)
- **Vista de reportes** (`/reports`): Filtros por tipo, estado, rango de fechas
- **Exportar a Excel**: PhpSpreadsheet con formato, colores y anchos automáticos
- **Exportar a PDF**: Dompdf con diseño de tabla y logo

---

## [1.2.1] — 2026-02-23

### 🎨 Mejoras de UI: Sidebar Responsive + Branding

#### Sidebar Responsive con Bootstrap Offcanvas
- Reemplazado sidebar custom CSS/JS por Bootstrap 5 `offcanvas-lg`
- En mobile: slide-in desde la izquierda con backdrop
- En desktop: sidebar fijo colapsable con persistencia `localStorage`
- Eliminado overlay custom, simplificado `app.js`

#### Branding
- **Logo**: Imagen `kodigo.jpg` integrada en headers mobile y desktop del sidebar
- **Footer**: Actualizado a "Desarrollado con ❤️ por tu equipo (Engineer Manager + unas cuantas AI)"

#### Corrección de Color del Sidebar
- Fix: Sidebar aparecía blanco tras conversión a offcanvas
- Añadido `--bs-offcanvas-bg: var(--sidebar-bg)` y `background: var(--sidebar-bg) !important` en `.sidebar`

---

## [1.2.0] — 2026-02-23

### 🎨 Rediseño Completo de UI/UX

Se realizó una modernización integral de toda la interfaz de usuario siguiendo las mejores prácticas de diseño de dashboards administrativos.

#### Layout y Navegación
- **Sidebar colapsible**: Nueva barra lateral que se colapsa en desktop (72px) con persistencia en `localStorage`
- **Overlay móvil**: Sidebar con overlay semi-transparente en pantallas < 992px
- **Header mejorado**: Toggle móvil, soporte para breadcrumbs, avatar de usuario con gradiente
- **Footer minimal**: Diseño limpio y responsive

#### Sistema de Diseño
- **Variables CSS**: Implementación de custom properties para theming consistente
  - `--sidebar-width: 260px`
  - `--sidebar-collapsed-width: 72px`
  - `--header-height: 64px`
  - `--transition-speed: 0.25s`
- **Badges sutiles**: Uso de `bg-*-subtle text-*` para estados más elegantes
- **Stat Cards**: Nuevo componente `.stat-card` con iconos y valores destacados
- **Empty States**: Diseño consistente para estados vacíos en todas las vistas
- **Form Sections**: Agrupación visual de campos con `.form-section` y `.form-section-title`

#### Vistas Actualizadas
| Vista | Cambios |
|-------|---------|
| `layouts/main.php` | Overlay móvil, mejor estructura, `container-fluid` |
| `partials/sidebar.php` | Diseño moderno, indicadores activos, tooltips en collapsed |
| `partials/header.php` | Toggle móvil, breadcrumbs, avatar mejorado |
| `partials/footer.php` | Diseño minimal flex |
| `dashboard/index.php` | Welcome section, stat cards modernos, quick actions grid |
| `cohorts/index.php` | Stats summary, tabla reducida a 7 columnas, responsive |
| `cohorts/create.php` | Breadcrumbs, form sections, validación HTML5 |
| `cohorts/edit.php` | Mismo estilo que create, fechas calculadas visibles |
| `cohorts/show.php` | Header card, layout 2 columnas, cards organizados |
| `users/index.php` | Stats summary, tabla simplificada con roles |
| `users/create.php` | Form sections: Cuenta, Rol/Estado, Seguridad |
| `users/edit.php` | Mismo patrón que create |
| `marketing/index.php` | Cards con hover effects, diseño grid responsive |
| `marketing/show.php` | Header card, modales centrados, badges mejorados |
| `alerts/index.php` | Stat cards, tablas con columnas responsivas |
| `auth/login.php` | Diseño moderno con gradientes, brand icon animado |
| `errors/404.php` | Diseño visual atractivo con gradientes |

#### CSS (`public/assets/css/app.css`)
- **~400 líneas** de estilos custom
- Secciones: Variables, Sidebar, Header, Footer, Cards, Tables, Buttons, Forms, Badges, Empty State, Responsive
- Breakpoints: `991.98px` (sidebar), `575.98px` (mobile)

#### JavaScript (`public/assets/js/app.js`)
- **~130 líneas** de código modular ES6
- `initSidebar()`: Toggle móvil + collapse desktop + localStorage
- `initTooltips()`: Bootstrap tooltips automáticos
- `initConfirmDialogs()`: Confirmación via `data-confirm`
- `initFormValidation()`: Validación Bootstrap 5
- `initTableResponsive()`: Mejoras de scroll en tablas

---

## [1.1.0] — 2026-02-22

### 🔐 Sistema de Autenticación y Autorización

Se implementó un sistema completo de autenticación con 4 roles diferenciados.

#### Roles del Sistema
| Rol | Permisos |
|-----|----------|
| `admin` | Acceso total: cohortes, usuarios, marketing, alertas |
| `admissions_b2b` | Cohortes (solo lectura), comentarios B2B |
| `admissions_b2c` | Cohortes (solo lectura), comentarios B2C |
| `marketing` | Workflow de marketing, actualización de etapas |

#### Nuevas Tablas de Base de Datos
```sql
-- Usuarios del sistema
CREATE TABLE users (
    id, username, email, password_hash, full_name,
    role ENUM('admin','admissions_b2b','admissions_b2c','marketing'),
    is_active, last_login_at, created_at, updated_at
);

-- Sesiones activas
CREATE TABLE sessions (
    id, user_id, token, ip_address, user_agent,
    created_at, expires_at
);

-- Etapas de marketing por cohorte
CREATE TABLE marketing_stages (
    id, cohort_id, stage_name, status, risk_notes,
    updated_by, updated_at, created_at
);

-- Comentarios y riesgos
CREATE TABLE cohort_comments (
    id, cohort_id, user_id, category, body, created_at
);

-- Auditoría de acciones
CREATE TABLE audit_log (
    id, user_id, action, entity_type, entity_id,
    old_values, new_values, ip_address, created_at
);
```

#### Arquitectura de Auth
- `App\Core\Auth`: Clase estática para sesión, roles, flash messages
- `App\Repositories\UserRepository`: CRUD de usuarios
- `App\Repositories\SessionRepository`: Gestión de sesiones
- `App\Services\AuthService`: Login, logout, validación
- `App\Services\UserService`: Lógica de usuarios
- `App\Controllers\AuthController`: Login/logout HTTP
- `App\Controllers\UserController`: CRUD usuarios (admin)

#### Workflow de Marketing
- 7 etapas predefinidas: strategy, content, ads, organic, events, partnerships, analytics
- Estados: `pending`, `completed`, `at_risk`
- Documentación obligatoria de riesgos
- Historial de actualizaciones por usuario

#### Sistema de Alertas
- Dashboard de riesgos para administradores
- Comentarios categorizados: general, risk, admission, marketing
- Conteo de cohortes con riesgos activos

#### Migración
- `database/migrations/003_auth_system.sql`
- Usuarios seed: admin, admissions_b2b, admissions_b2c, marketing
- Contraseña por defecto: `admin123`

---

## [1.0.1] — 2026-02-21

### 📊 Campos Extendidos de Cohortes

Se añadieron 16 nuevas columnas a la tabla `cohorts` para tracking completo.

#### Nuevos Campos
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `cohort_code` | VARCHAR(50) | Código único de cohorte |
| `correlative_number` | INT | Número correlativo secuencial |
| `total_admission_target` | INT | Meta total de admisiones |
| `b2b_admission_target` | INT | Meta de admisiones B2B |
| `b2c_admissions` | INT | Admisiones B2C actuales |
| `admission_deadline_date` | DATE | Fecha límite de admisión |
| `training_date_50` | DATE | Fecha 50% entrenamiento (calculada) |
| `training_date_75` | DATE | Fecha 75% entrenamiento (calculada) |
| `related_project` | VARCHAR(255) | Proyecto relacionado |
| `assigned_coach` | VARCHAR(255) | Coach asignado |
| `bootcamp_type` | VARCHAR(100) | Tipo de bootcamp |
| `assigned_class_schedule` | VARCHAR(255) | Horario asignado |
| `training_status` | ENUM | Estado: not_started, in_progress, completed, cancelled |

#### Cálculo Automático de Fechas
- `training_date_50`: 50% entre start_date y end_date
- `training_date_75`: 75% entre start_date y end_date
- Calculado automáticamente en `CohortService::calculateTrainingDates()`

---

## [1.0.0] — 2026-02-20

### 🚀 Release Inicial

MVP funcional con CRUD completo de cohortes.

#### Funcionalidades
- Dashboard con estadísticas en tiempo real
- CRUD completo de cohortes
- Sistema de rutas con Router custom
- Arquitectura MVC + Service Layer + Repository
- Bootstrap 5 para UI responsive
- Base de datos MySQL con PDO

#### Stack Tecnológico
- PHP 8.2+
- MySQL 8.0+
- Bootstrap 5.3.2
- Bootstrap Icons 1.11.3
- Vanilla JavaScript ES6

---

*Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/)*
