# PRD — Product Requirements Document
## Cohort Monitor v1.5

---

## 1. Resumen del Producto

| Campo              | Detalle                                                        |
|--------------------|----------------------------------------------------------------|
| **Nombre**         | Cohort Monitor                                                 |
| **Versión**        | 1.5.0                                                          |
| **Tipo**           | Aplicación web de gestión interna                              |
| **Stack**          | PHP 8.2 · MySQL · Bootstrap 5 · JavaScript ES6                |
| **Framework**      | MVC custom (sin frameworks de terceros)                        |
| **Composer**       | PhpSpreadsheet 5.4 · Dompdf 3.1                               |
| **Estado**         | ✅ Producción — Auth + Marketing + Cuenta + Import + Reports   |

### Propósito
Cohort Monitor es una plataforma web para **crear, monitorear y administrar cohortes educativas** (grupos de estudiantes organizados por programa y periodo). Permite a administradores tener visibilidad en tiempo real del estado de cada cohorte a través de un dashboard centralizado.

### Usuarios Objetivo
- Administradores de programas educativos
- Coordinadores académicos
- Personal de gestión de bootcamps / escuelas técnicas

---

## 2. Objetivos del Producto

| #  | Objetivo                                        | Estado      |
|----|------------------------------------------------|-------------|
| O1 | CRUD completo de cohortes                      | ✅ Completo |
| O2 | Dashboard con estadísticas en tiempo real       | ✅ Completo |
| O3 | Arquitectura escalable y mantenible             | ✅ Completo |
| O4 | Preparado para expansión (Students, API REST)   | ✅ Preparado|
| O5 | Gestión de estudiantes por cohorte              | 🔲 Futuro   |
| O6 | Sistema de reportes y métricas                  | 🔲 Futuro   |
| O7 | Autenticación y roles de usuario                | ✅ Completo |
| O8 | API REST para integraciones externas            | 🔲 Futuro   |
| O9 | Workflow de Marketing con etapas                | ✅ Completo |
| O10| UI/UX Responsive moderna tipo Dashboard         | ✅ Completo |
| O11| Importación masiva de cohortes desde Excel/CSV  | ✅ Completo |
| O12| Reportes con exportación Excel y PDF            | ✅ Completo |
| O13| Módulo Mi Cuenta (self-service todos los roles) | ✅ Completo |
| O14| Admin: Toggle status, reset password, protección| ✅ Completo |

---

## 3. Funcionalidades Implementadas (v1.0)

### 3.1 Dashboard (`/`)
| Funcionalidad                       | Detalle                                      |
|-------------------------------------|----------------------------------------------|
| Welcome Section                     | Saludo con nombre de usuario y fecha actual   |
| Stat Card "Total Cohortes"          | Cuenta total con icono y badge subtle         |
| Stat Card "Cohortes Activas"        | Filtro status = `active` con indicador visual |
| Stat Card "Usuarios"                | Total usuarios del sistema                    |
| Stat Card "En Riesgo"               | Cohortes con etapas de marketing `at_risk`    |
| Quick Actions                       | Grid de acciones: Nueva Cohorte, Ver Cohortes, Usuarios, Alertas |
| Info del sistema                    | Versión de PHP, hora servidor, versión app    |

### 3.2 Gestión de Cohortes (`/cohorts`)
| Acción     | Ruta                    | Método HTTP | Descripción                          |
|------------|-------------------------|-------------|--------------------------------------|
| Listar     | `/cohorts`              | GET         | Tabla con todas las cohortes         |
| Crear      | `/cohorts/create`       | GET         | Formulario de creación               |
| Guardar    | `/cohorts`              | POST        | Persistir nueva cohorte              |
| Ver        | `/cohorts/{id}`         | GET         | Detalle individual con acciones      |
| Editar     | `/cohorts/{id}/edit`    | GET         | Formulario de edición pre-cargado    |
| Actualizar | `/cohorts/{id}`         | PUT         | Aplicar cambios (via `_method`)      |
| Eliminar   | `/cohorts/{id}`         | DELETE      | Eliminar con confirmación JS         |

### 3.3 Campos de una Cohorte
| Campo                    | Tipo SQL                                   | Requerido | Descripción                   |
|--------------------------|-------------------------------------------|-----------|-------------------------------|
| `id`                     | INT UNSIGNED AUTO_INCREMENT                | Auto      | Identificador único           |
| `name`                   | VARCHAR(255)                               | ✅ Sí     | Nombre de la cohorte          |
| `cohort_code`            | VARCHAR(50)                                | No        | Código único de cohorte       |
| `correlative_number`     | INT                                        | No        | Número correlativo secuencial |
| `description`            | TEXT                                       | No        | Descripción del programa      |
| `start_date`             | DATE                                       | No        | Fecha de inicio               |
| `end_date`               | DATE                                       | No        | Fecha de finalización         |
| `training_date_50`       | DATE                                       | Auto      | 50% entre start/end (calculado)|
| `training_date_75`       | DATE                                       | Auto      | 75% entre start/end (calculado)|
| `total_admission_target` | INT                                        | No        | Meta total de admisiones      |
| `b2b_admission_target`   | INT                                        | No        | Meta admisiones B2B           |
| `b2c_admissions`         | INT                                        | No        | Admisiones B2C actuales       |
| `admission_deadline_date`| DATE                                       | No        | Fecha límite de admisión      |
| `related_project`        | VARCHAR(255)                               | No        | Proyecto relacionado          |
| `assigned_coach`         | VARCHAR(255)                               | No        | Coach asignado                |
| `bootcamp_type`          | VARCHAR(100)                               | No        | Tipo de bootcamp              |
| `assigned_class_schedule`| VARCHAR(255)                               | No        | Horario de clases asignado    |
| `training_status`        | ENUM('not_started','in_progress','completed','cancelled') | No | Estado del entrenamiento |
| `status`                 | ENUM('active','inactive','archived')       | ✅ Sí     | Estado actual (default: active)|
| `created_at`             | DATETIME                                   | Auto      | Timestamp de creación         |
| `updated_at`             | DATETIME                                   | Auto      | Timestamp de actualización    |

### 3.4 UI / Interfaz (v1.2 — Rediseño Completo)
| Componente         | Tecnología       | Descripción                                     |
|--------------------|------------------|-------------------------------------------------|
| Layout principal   | Bootstrap 5.3    | Sidebar colapsable + header + overlay móvil     |
| Sidebar            | CSS + JS custom  | Dashboard, Cohortes, Usuarios, Marketing, Alertas |
| Header             | Bootstrap navbar | Toggle móvil, breadcrumbs, avatar con gradiente |
| Stat Cards         | `.stat-card`     | Componente custom con icono, valor y label      |
| Tablas responsive  | `.table-responsive` | Columnas ocultas en móvil `d-none d-md-*`    |
| Form Sections      | `.form-section`  | Agrupación visual de campos en formularios      |
| Badges             | `bg-*-subtle`    | Estados con colores sutiles y texto legible     |
| Empty States       | `.empty-state`   | Diseño consistente para vistas sin datos        |
| CSS Variables      | app.css          | `--sidebar-width`, `--header-height`, theming   |
| JS modular         | app.js (ES6)     | Sidebar toggle, localStorage, tooltips, validación |

#### Variables CSS del Sistema de Diseño
```css
:root {
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 72px;
    --header-height: 64px;
    --transition-speed: 0.25s;
}
```

### 3.5 Sistema de Autenticación
| Funcionalidad                       | Detalle                                      |
|-------------------------------------|----------------------------------------------|
| Login                               | Formulario con username/password + remember  |
| Logout                              | Destrucción de sesión y token                |
| Sesiones                            | Token almacenado en BD con expiración        |
| Roles                               | admin, admissions_b2b, admissions_b2c, marketing |
| Middleware                          | Verificación de permisos en cada ruta        |
| Audit Log                           | Registro de acciones por usuario             |

### 3.6 Workflow de Marketing
| Funcionalidad                       | Detalle                                      |
|-------------------------------------|----------------------------------------------|
| 7 Etapas predefinidas               | strategy, content, ads, organic, events, partnerships, analytics |
| Estados de etapa                    | pending, completed, at_risk                  |
| Notas de riesgo                     | Documentación obligatoria para `at_risk`     |
| Historial                           | Registro de actualizaciones por usuario      |
| Vista de cohorte                    | Cards con progreso visual por etapa          |

### 3.7 Sistema de Alertas
| Funcionalidad                       | Detalle                                      |
|-------------------------------------|----------------------------------------------|
| Dashboard de riesgos                | Lista de cohortes con etapas en riesgo       |
| Filtros por estado                  | Todos, at_risk, pending, completed           |
| Comentarios                         | Sistema de comentarios categorizados         |
| Categorías                          | general, risk, admission, marketing          |

### 3.8 Importación Masiva de Cohortes (`/cohorts/import`)
| Funcionalidad                       | Detalle                                      |
|-------------------------------------|----------------------------------------------|
| Upload de archivos                  | Drag & drop + selector: `.xlsx`, `.xls`, `.csv` |
| Validación por fila                 | Campos obligatorios, formatos, ENUMs, rangos |
| Detección de duplicados             | Por nombre + fecha de inicio                 |
| Bulk insert transaccional           | Todo o nada — rollback ante error fatal      |
| Plantilla descargable               | Excel con headers, validaciones y ejemplos   |
| Acceso                              | Solo admin                                   |

### 3.9 Reportes (`/reports`)
| Funcionalidad                       | Detalle                                      |
|-------------------------------------|----------------------------------------------|
| Vista de reportes                   | Filtros por tipo, estado, rango de fechas    |
| Exportar a Excel                    | PhpSpreadsheet: formato, colores, anchos     |
| Exportar a PDF                      | Dompdf: tabla formateada con logo            |
| Acceso                              | Todos los roles autenticados                 |

### 3.10 Módulo Mi Cuenta (`/account`)
| Funcionalidad                       | Detalle                                      |
|-------------------------------------|----------------------------------------------|
| Vista de perfil                     | Tarjeta con avatar de iniciales, rol, estado, último acceso, fecha registro |
| Editar perfil                       | Nombre completo + email (username no editable) |
| Cambiar contraseña                  | Valida contraseña actual, nueva (mín. 8 chars) + confirmación |
| Sesión actualizada                  | Cambios reflejados inmediatamente en sesión  |
| Acceso                              | Todos los roles autenticados                 |

### 3.11 Administración de Usuarios (Mejoras Admin)
| Funcionalidad                       | Detalle                                      |
|-------------------------------------|----------------------------------------------|
| Toggle activar/desactivar           | Cambio de estado con un clic                 |
| Restablecer contraseña              | Genera clave aleatoria 12 chars, muestra al admin |
| Protección último admin             | No permite eliminar/desactivar al último admin activo |
| Columna "Último Acceso"             | Fecha/hora del último login en tabla de usuarios |
| Acciones expandidas                 | Toggle, reset password, edit, delete por usuario |

---

## 4. Modelo de Datos (Database Schema)

### 4.1 Tabla `cohorts` (21 columnas)
```sql
CREATE TABLE cohorts (
    id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                     VARCHAR(255) NOT NULL,
    cohort_code              VARCHAR(50) NULL,
    correlative_number       INT NULL,
    description              TEXT NULL,
    start_date               DATE NULL,
    end_date                 DATE NULL,
    training_date_50         DATE NULL,
    training_date_75         DATE NULL,
    total_admission_target   INT NULL,
    b2b_admission_target     INT NULL,
    b2c_admissions           INT NULL,
    admission_deadline_date  DATE NULL,
    related_project          VARCHAR(255) NULL,
    assigned_coach           VARCHAR(255) NULL,
    bootcamp_type            VARCHAR(100) NULL,
    assigned_class_schedule  VARCHAR(255) NULL,
    training_status          ENUM('not_started','in_progress','completed','cancelled') DEFAULT 'not_started',
    status                   ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at               DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at               DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cohorts_status (status),
    INDEX idx_cohorts_dates  (start_date, end_date)
);
```

### 4.2 Tabla `users`
```sql
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(100) NOT NULL UNIQUE,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(255) NULL,
    role            ENUM('admin','admissions_b2b','admissions_b2c','marketing') DEFAULT 'marketing',
    is_active       BOOLEAN DEFAULT TRUE,
    last_login_at   DATETIME NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 4.3 Tabla `sessions`
```sql
CREATE TABLE sessions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token       VARCHAR(255) NOT NULL UNIQUE,
    ip_address  VARCHAR(45) NULL,
    user_agent  TEXT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 4.4 Tabla `marketing_stages`
```sql
CREATE TABLE marketing_stages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cohort_id   INT UNSIGNED NOT NULL,
    stage_name  VARCHAR(100) NOT NULL,
    status      ENUM('pending','completed','at_risk') DEFAULT 'pending',
    risk_notes  TEXT NULL,
    updated_by  INT UNSIGNED NULL,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### 4.5 Tabla `cohort_comments`
```sql
CREATE TABLE cohort_comments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cohort_id   INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    category    ENUM('general','risk','admission','marketing') DEFAULT 'general',
    body        TEXT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 4.6 Tabla `audit_log`
```sql
CREATE TABLE audit_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id   INT UNSIGNED NULL,
    old_values  JSON NULL,
    new_values  JSON NULL,
    ip_address  VARCHAR(45) NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### 4.7 Tabla `students` (preparada, sin CRUD aún)
```sql
CREATE TABLE students (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name  VARCHAR(100)    NOT NULL,
    last_name   VARCHAR(100)    NOT NULL,
    email       VARCHAR(255)    NULL UNIQUE,
    cohort_id   INT UNSIGNED    NULL,
    status      ENUM('active', 'inactive', 'graduated', 'dropped') DEFAULT 'active',
    created_at  DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE SET NULL
);
```

### 4.8 Datos Seed (precargados)
| ID | Nombre                              | Status   | Periodo                 |
|----|-------------------------------------|----------|-------------------------|
| 1  | Spring 2026 — Full Stack Web Dev    | active   | 2026-03-01 → 2026-08-31|
| 2  | Winter 2025 — Data Science          | active   | 2025-11-01 → 2026-04-30|
| 3  | Fall 2025 — UX/UI Design            | archived | 2025-09-01 → 2026-02-28|

---

## 5. Arquitectura del Sistema

### 5.1 Patrón: MVC + Service Layer + Repository

```
┌─────────────────────────────────────────────────────────────────┐
│                        HTTP REQUEST                             │
│                    (GET /cohorts/1)                              │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  public/index.php  (Front Controller)                           │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  1. Define APP_ROOT                                     │    │
│  │  2. Carga bootstrap/app.php (autoloader + env + helpers)│    │
│  │  3. Crea Router y carga routes/web.php                  │    │
│  │  4. $router->dispatch()                                 │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  Router  (app/Core/Router.php)                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  • Compara URI contra patrones regex                    │    │
│  │  • Extrae parámetros dinámicos {id}                     │    │
│  │  • Soporta _method override (PUT/DELETE en forms)       │    │
│  │  • Instancia el Controller y llama al método            │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  Controller  (app/Controllers/CohortController.php)             │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  SOLO maneja lógica HTTP:                               │    │
│  │  • Lee input del request ($this->input())               │    │
│  │  • Llama al Service correspondiente                     │    │
│  │  • Renderiza vista o redirige                           │    │
│  │  • NUNCA toca la base de datos directamente             │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  Service  (app/Services/CohortService.php)                      │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Contiene la LÓGICA DE NEGOCIO:                         │    │
│  │  • Valida datos (nombre requerido, fechas coherentes)   │    │
│  │  • Orquesta operaciones complejas                       │    │
│  │  • Delega persistencia al Repository                    │    │
│  │  • NUNCA genera HTML ni maneja HTTP                     │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  Repository  (app/Repositories/CohortRepository.php)            │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  SOLO acceso a datos:                                   │    │
│  │  • Queries SQL con PDO prepared statements              │    │
│  │  • findAll(), findById(), create(), update(), delete()  │    │
│  │  • NUNCA contiene lógica de negocio                     │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  Database  (app/Core/Database.php)                              │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Singleton PDO wrapper:                                 │    │
│  │  • query() → SELECT (retorna array de filas)            │    │
│  │  • execute() → INSERT/UPDATE/DELETE (retorna rowCount)  │    │
│  │  • Soporte de transacciones                             │    │
│  │  • Lee config de config/database.php + .env             │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  MySQL Database                                                 │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  cohort_monitor                                         │    │
│  ├── cohorts (21 columnas, CRUD activo)                  │    │
│  ├── users (auth + roles)                               │    │
│  ├── sessions (tokens de sesión)                         │    │
│  ├── marketing_stages (workflow)                        │    │
│  ├── cohort_comments (comentarios)                      │    │
│  ├── audit_log (auditoría)                              │    │
│  │  └── students (tabla preparada, FK → cohorts)           │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

### 5.2 Mapa de Namespaces (PSR-4)

| Namespace             | Directorio              | Responsabilidad          |
|-----------------------|-------------------------|--------------------------|
| `App\Core\`           | `app/Core/`             | Infraestructura base     |
| `App\Controllers\`    | `app/Controllers/`      | Capa HTTP                |
| `App\Services\`       | `app/Services/`         | Lógica de negocio        |
| `App\Repositories\`   | `app/Repositories/`     | Acceso a datos           |
| `App\Models\`         | `app/Models/`           | Entidades de datos       |

### 5.3 Ciclo de Vida del Request

```
Browser → GET /cohorts/1
    │
    ├─ 1. public/index.php ← Punto de entrada único
    ├─ 2. bootstrap/app.php ← Carga .env + autoloader + helpers
    ├─ 3. Router::dispatch() ← Matchea /cohorts/{id} → CohortController::show
    ├─ 4. CohortController::show("1") ← Lee el parámetro {id}
    ├─ 5. CohortService::getCohortById(1) ← Busca en servicio
    ├─ 6. CohortRepository::findById(1) ← Ejecuta SQL
    ├─ 7. Database::query("SELECT...") ← PDO prepared statement
    ├─ 8. MySQL → devuelve fila
    ├─ 9. Controller::view("cohorts.show", $data) ← Renderiza vista
    ├─ 10. View → captura con ob_start() → inserta en layout
    └─ 11. HTML Response ← Enviado al navegador
```

---

## 6. Estructura de Archivos Completa

```
cohort-monitor/                          ~65 archivos
│
├── public/                              DOCUMENT ROOT del servidor web
│   ├── index.php                        Front Controller (entrada única)
│   ├── .htaccess                        Reescritura de URLs para Apache
│   └── assets/
│       ├── css/app.css                  ~400 líneas: variables CSS, sidebar, cards, responsive
│       ├── js/app.js                    ~130 líneas: sidebar toggle, localStorage, validación
│       └── images/.gitkeep              Placeholder para assets gráficos
│
├── app/                                 CÓDIGO FUENTE DE LA APLICACIÓN
│   ├── Core/                            Núcleo del mini-framework
│   │   ├── Router.php                   Sistema de rutas con regex y parámetros {param}
│   │   ├── Controller.php               Clase base: view(), json(), redirect(), input()
│   │   ├── Database.php                 Singleton PDO: query(), execute(), transacciones
│   │   └── Auth.php                     Sesión, roles, permisos, flash messages
│   │
│   ├── Controllers/                     Capa HTTP (request → response)
│   │   ├── DashboardController.php      Renderiza dashboard con stats
│   │   ├── CohortController.php         CRUD completo: index/create/store/show/edit/update/destroy
│   │   ├── UserController.php           CRUD usuarios + toggle status + reset password (admin)
│   │   ├── AccountController.php        Perfil self-service: profile/updateProfile/changePassword
│   │   ├── AuthController.php           Login/logout HTTP
│   │   ├── ImportCohortController.php   Importación masiva: showForm/handleImport/downloadTemplate
│   │   ├── ReportController.php         Reportes: index/exportExcel/exportPdf
│   │   ├── MarketingController.php      Workflow de marketing por cohorte
│   │   └── AlertController.php          Dashboard de alertas y riesgos
│   │
│   ├── Services/                        Lógica de negocio
│   │   ├── DashboardService.php         Agrega stats: totalCohorts, activeCohorts, etc.
│   │   ├── CohortService.php            Validación + cálculo fechas + orquestación CRUD
│   │   ├── CohortImportService.php      Lectura Excel/CSV, validación, normalización, bulk insert
│   │   ├── UserService.php              Usuarios: CRUD + profile + password + toggle + reset
│   │   ├── AuthService.php              Login, logout, validación de sesiones
│   │   ├── MarketingService.php         Gestión de etapas de marketing
│   │   └── AlertService.php             Detección de riesgos y alertas
│   │
│   ├── Repositories/                    Acceso a datos (SQL puro)
│   │   ├── CohortRepository.php         findAll, findById, create, update, delete, count
│   │   ├── UserRepository.php           CRUD de usuarios
│   │   ├── SessionRepository.php        Gestión de sesiones activas
│   │   ├── MarketingStageRepository.php Etapas de marketing por cohorte
│   │   ├── CommentRepository.php        Comentarios de cohortes
│   │   └── AuditRepository.php          Registro de auditoría
│   │
│   ├── Models/                          Entidades de datos (PHP 8.1+ constructor promotion)
│   │   ├── Cohort.php                   Entidad cohorte: fromArray(), toArray(), isActive()
│   │   ├── User.php                     Entidad usuario: roles, permisos
│   │   └── Student.php                  Entidad estudiante: fromArray(), toArray(), fullName()
│   │
│   └── Views/                           Capa de presentación (HTML + Bootstrap 5)
│       ├── layouts/main.php             Layout principal con overlay móvil
│       ├── partials/
│       │   ├── sidebar.php              Sidebar colapsable con localStorage
│       │   ├── header.php               Toggle móvil, breadcrumbs, avatar
│       │   └── footer.php               Pie de página minimal flex
│       ├── dashboard/index.php          Stat cards + quick actions grid
│       ├── cohorts/
│       │   ├── index.php                Stats + tabla responsive 7 columnas
│       │   ├── create.php               Form sections con validación
│       │   ├── show.php                 Layout 2 columnas con cards
│       │   └── edit.php                 Breadcrumbs + form sections
│       ├── users/
│       │   ├── index.php                Stats + tabla con toggle/reset/último acceso
│       │   ├── create.php               Secciones: Cuenta, Rol, Seguridad
│       │   └── edit.php                 Form sections con roles
│       ├── account/
│       │   └── profile.php              Mi Cuenta: tarjeta resumen + editar perfil + cambiar contraseña
│       ├── cohorts/
│       │   └── import.php               Importación masiva: drag & drop + resultados
│       ├── marketing/
│       │   ├── index.php                Cards con hover + grid responsive
│       │   └── show.php                 Header card + modales de etapas
│       ├── alerts/index.php             Stat cards + tabla de riesgos
│       ├── auth/login.php               Diseño con gradientes modernos
│       └── errors/404.php               Error 404 visual en español
│
├── bootstrap/
│   └── app.php                          Inicialización: loadEnv(), autoloader PSR-4, helpers
│
├── config/
│   ├── app.php                          Config general: name, env, debug, url, timezone
│   └── database.php                     Config DB: host, port, database, username, password
│
├── routes/
│   └── web.php                          ~35 rutas (dashboard + cohorts + import + users + account + auth + marketing + reports + alerts)
│
├── database/
│   ├── schema.sql                       DDL: CREATE DATABASE + 7 tablas + índices + FK
│   └── migrations/
│       ├── 001_initial_schema.sql       Cohorts + Students
│       ├── 002_cohort_extended_fields.sql  16 columnas adicionales
│       └── 003_auth_system.sql          Users, Sessions, Marketing, Comments, Audit
│
├── docs/
│   ├── PRD.md                           Este documento
│   └── CHANGELOG.md                     Historial de cambios por versión
│
├── storage/
│   ├── logs/.gitkeep                    Directorio para logs futuros
│   └── cache/.gitkeep                   Directorio para cache futuro
│
├── .env                                 Variables de entorno (NO se commitea)
├── .env.example                         Template de .env para nuevos devs
├── .gitignore                           Ignora: .env, vendor/, node_modules/, storage/*
├── composer.json                        Metadatos del proyecto + PSR-4 autoload config
└── README.md                            Documentación básica del proyecto
```

---

## 7. Stack Tecnológico

| Capa          | Tecnología       | Versión | Propósito                              |
|---------------|-----------------|---------|----------------------------------------|
| Backend       | PHP             | 8.2+    | Lógica servidor, MVC, routing          |
| Base de datos | MySQL           | 8.0+    | Persistencia relacional con InnoDB     |
| Conexión DB   | PDO             | —       | Prepared statements, protección SQL    |
| Frontend CSS  | Bootstrap       | 5.3.2   | Grid, componentes, responsive design   |
| Iconos        | Bootstrap Icons | 1.11.3  | Iconografía consistente UI             |
| Frontend JS   | Vanilla ES6+    | —       | Interacciones (modular, sin jQuery)    |
| Excel Export  | PhpSpreadsheet  | 5.4.0   | Lectura/escritura archivos Excel/CSV   |
| PDF Export    | Dompdf          | 3.1.4   | Generación de reportes en PDF          |
| Servidor dev  | PHP built-in    | —       | `php -S localhost:8000 -t public`      |
| Servidor prod | Apache          | 2.4+    | Con mod_rewrite (.htaccess)            |

---

## 8. Reglas de Arquitectura

### Lo que PUEDE hacer cada capa:

| Capa          | SÍ puede                                | NO puede                              |
|---------------|------------------------------------------|---------------------------------------|
| **Controller**| Leer HTTP input, llamar Services, renderizar Views, redirigir | Hacer queries SQL, contener lógica de negocio, acceder a Repository directamente |
| **Service**   | Validar datos, orquestar operaciones, llamar Repositories | Generar HTML, manejar HTTP, acceder a $_GET/$_POST directamente |
| **Repository**| Ejecutar queries SQL, usar Database singleton | Contener validaciones, generar HTML, manejar HTTP |
| **Model**     | Representar datos, convertir formatos (toArray/fromArray) | Contener queries, lógica de negocio, HTML |
| **View**      | Mostrar datos con HTML, usar helpers (htmlspecialchars), lógica de presentación mínima (if/foreach) | Ejecutar queries, instanciar Services, contener lógica de negocio |

### Flujo de dependencias (unidireccional):
```
Controller → Service → Repository → Database → MySQL
     ↓
   View (solo recibe datos, nunca llama hacia arriba)
```

---

## 9. Rutas Registradas

### Auth
| Nombre          | Método | URI                  | Controller                  | Acción    |
|-----------------|--------|----------------------|-----------------------------|-----------|
| login.form      | GET    | `/login`             | AuthController              | showLogin |
| login.submit    | POST   | `/login`             | AuthController              | login     |
| logout          | POST   | `/logout`            | AuthController              | logout    |

### Dashboard
| Nombre          | Método | URI                  | Controller                  | Acción    |
|-----------------|--------|----------------------|-----------------------------|-----------|
| dashboard       | GET    | `/`                  | DashboardController         | index     |

### Cohorts
| Nombre          | Método | URI                  | Controller                  | Acción    |
|-----------------|--------|----------------------|-----------------------------|-----------|
| cohorts.index   | GET    | `/cohorts`           | CohortController            | index     |
| cohorts.create  | GET    | `/cohorts/create`    | CohortController            | create    |
| cohorts.store   | POST   | `/cohorts`           | CohortController            | store     |
| cohorts.show    | GET    | `/cohorts/{id}`      | CohortController            | show      |
| cohorts.edit    | GET    | `/cohorts/{id}/edit` | CohortController            | edit      |
| cohorts.update  | PUT    | `/cohorts/{id}`      | CohortController            | update    |
| cohorts.destroy | DELETE | `/cohorts/{id}`      | CohortController            | destroy   |

### Account (Todos los roles)
| Nombre          | Método | URI                  | Controller                  | Acción         |
|-----------------|--------|----------------------|-----------------------------|----------------|
| account.profile | GET    | `/account`           | AccountController           | profile        |
| account.update  | POST   | `/account`           | AccountController           | updateProfile  |
| account.password| POST   | `/account/password`  | AccountController           | changePassword |

### Users (solo Admin)
| Nombre          | Método | URI                          | Controller                  | Acción         |
|-----------------|--------|------------------------------|-----------------------------|----------------|
| users.index     | GET    | `/users`                     | UserController              | index          |
| users.create    | GET    | `/users/create`              | UserController              | create         |
| users.store     | POST   | `/users`                     | UserController              | store          |
| users.edit      | GET    | `/users/{id}/edit`           | UserController              | edit           |
| users.update    | PUT    | `/users/{id}`                | UserController              | update         |
| users.destroy   | DELETE | `/users/{id}`                | UserController              | destroy        |
| users.toggle    | POST   | `/users/{id}/toggle-status`  | UserController              | toggleStatus   |
| users.reset     | POST   | `/users/{id}/reset-password` | UserController              | resetPassword  |

### Import (solo Admin)
| Nombre              | Método | URI                        | Controller                  | Acción           |
|---------------------|--------|----------------------------|-----------------------------|------------------|
| cohorts.import      | GET    | `/cohorts/import`          | ImportCohortController      | showForm         |
| cohorts.import.post | POST   | `/cohorts/import`          | ImportCohortController      | handleImport     |
| cohorts.import.tmpl | GET    | `/cohorts/import/template` | ImportCohortController      | downloadTemplate |

### Reports (Todos los roles)
| Nombre              | Método | URI                        | Controller                  | Acción       |
|---------------------|--------|----------------------------|-----------------------------|------------|
| reports.index       | GET    | `/reports`                 | ReportController            | index        |
| reports.excel       | GET    | `/reports/export/excel`    | ReportController            | exportExcel  |
| reports.pdf         | GET    | `/reports/export/pdf`      | ReportController            | exportPdf    |

### Marketing
| Nombre              | Método | URI                        | Controller                  | Acción      |
|---------------------|--------|----------------------------|-----------------------------|-------------|
| marketing.index     | GET    | `/marketing`               | MarketingController         | index       |
| marketing.show      | GET    | `/marketing/{id}`          | MarketingController         | show        |
| marketing.update    | POST   | `/marketing/{id}/stage`    | MarketingController         | updateStage |

### Alerts
| Nombre          | Método | URI                  | Controller                  | Acción    |
|-----------------|--------|----------------------|-----------------------------|-----------|
| alerts.index    | GET    | `/alerts`            | AlertController             | index     |

---

## 10. Validaciones Implementadas

| Regla                                   | Capa    | Ubicación                     |
|-----------------------------------------|---------|-------------------------------|
| Nombre de cohorte requerido             | Service | CohortService::validate()     |
| Fecha inicio < fecha fin                | Service | CohortService::validate()     |
| Campos HTML5 `required`                 | View    | Formularios create/edit       |
| Confirmación antes de eliminar          | View    | `onsubmit="return confirm()"` |
| SQL Injection prevention (PDO prepared) | Core    | Database::query/execute()     |
| XSS prevention (htmlspecialchars)       | View    | Todas las vistas              |

---

## 11. Seguridad Implementada

| Medida                          | Implementación                                    |
|---------------------------------|---------------------------------------------------|
| SQL Injection                   | PDO prepared statements en TODAS las queries      |
| XSS (Cross-Site Scripting)      | `htmlspecialchars()` en toda salida a HTML         |
| Directory Traversal             | Solo `public/` es accesible vía web                |
| Error Exposure                  | APP_DEBUG=false oculta errores en producción       |
| Environment Secrets             | `.env` en `.gitignore` — nunca se commitea         |
| Autenticación                   | Sesiones con token en BD, expiración automática   |
| Autorización                    | Roles con permisos granulares por ruta             |
| Password Hashing                | `password_hash()` con BCRYPT                       |
| Audit Trail                     | Registro de acciones críticas en `audit_log`       |
| Session Fixation                | Regeneración de sesión en login                   |

---

## 12. Roadmap Futuro

| Prioridad | Feature                    | Estado                                            |
|-----------|---------------------------|---------------------------------------------------|
| P1        | CRUD de Students           | Modelo `Student.php`, tabla `students`, FK lista  |
| P1        | Autenticación              | ✅ COMPLETADO v1.1 — 4 roles implementados        |
| P1        | Workflow Marketing         | ✅ COMPLETADO v1.1 — 7 etapas con tracking        |
| P1        | UI/UX Responsive           | ✅ COMPLETADO v1.2 — Diseño dashboard moderno      |
| P1        | Sidebar Responsive         | ✅ COMPLETADO v1.2.1 — Bootstrap offcanvas-lg      |
| P1        | Reportes y exports         | ✅ COMPLETADO v1.3 — Excel (PhpSpreadsheet) + PDF (Dompdf) |
| P1        | Importación masiva         | ✅ COMPLETADO v1.4 — Excel/CSV con validación y duplicados |
| P1        | Módulo Mi Cuenta           | ✅ COMPLETADO v1.5 — Self-service + admin enhancements |
| P2        | API REST `/api/v1/*`       | Controller base soporta `$this->json()`, ruta api.php por crear |
| P2        | Paginación                 | Repository::count() ya existe, falta limit/offset |
| P3        | Búsqueda y filtros         | Repository::findByStatus() ya existe como ejemplo |
| P3        | Dashboard de métricas      | Gráficos y estadísticas avanzadas                 |
| P3        | Notificaciones             | Sistema de notificaciones en tiempo real          |

---

## 13. Cómo Ejecutar

```bash
# 1. Clonar
git clone https://github.com/Oscar-codes/cohort_monitor.git
cd cohort_monitor

# 2. Configurar entorno
cp .env.example .env
# Editar .env con credenciales de tu MySQL

# 3. Crear base de datos
mysql -u root -p < database/schema.sql

# 4. Levantar servidor de desarrollo
php -S localhost:8000 -t public

# 5. Abrir navegador
# http://localhost:8000
```

---

*Documento actualizado el 23 de febrero de 2026.*
*Versión del documento: 1.5*
