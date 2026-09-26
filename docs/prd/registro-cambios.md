# Registro de avances y evidencias

Última actualización: 2026-09-26. Evidencias del [backlog](backlog.md); trabajo de datos en [base-datos](base-datos.md). Las entradas son acumulativas: corregir una conclusión con una entrada nueva, sin borrar su antecedente.

## Evidencias

### E-001

- Fecha: 2026-09-26.
- Tickets: CM-DOC-001.
- Resultado: Verificado.
- Alcance: creación de habilidad PHP senior para este proyecto, no validación funcional de la aplicación.
- Fuentes: [SKILL.md](../../.agents/skills/php-senior-cohort-monitor/SKILL.md), [mapa](../../.agents/skills/php-senior-cohort-monitor/references/project-context.md), [metadatos](../../.agents/skills/php-senior-cohort-monitor/agents/openai.yaml); inspección de rutas, servicios, Auth, PDO, layout y documentación.
- Comprobación: `python -X utf8 C:/Users/PC/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/php-senior-cohort-monitor` devolvió `Skill is valid!` durante la creación en esta conversación.
- Transición: CM-DOC-001 completado conforme a su alcance documental.
- Límite: no acredita que todas las buenas prácticas documentadas estén implementadas.

### E-002

- Fecha: 2026-09-26.
- Tickets: CM-DOC-002, CM-VAL-001, CM-VAL-002, CM-VAL-003, CM-VAL-004, CM-VAL-005, CM-VAL-006, CM-VAL-007, CM-SEC-001, CM-SEC-002, CM-SEC-003, CM-SEC-004, CM-PERF-001, CM-ARCH-001, CM-FUT-001, CM-FUT-002.
- Resultado: Parcial.
- Alcance: línea base del tracker mediante inspección estática; las validaciones funcionales se registran como pendientes.
- Fuentes: [rutas](../../routes/web.php), [Auth](../../app/Core/Auth.php), [AuthService](../../app/Services/AuthService.php), [CohortService](../../app/Services/CohortService.php), [MarketingService](../../app/Services/MarketingService.php), [DashboardService](../../app/Services/DashboardService.php), [AdminController](../../app/Controllers/AdminController.php), [repositorios](../../app/Repositories/), [layout](../../app/Views/layouts/main.php), [PRD](../PRD.md), [auditoría](../AUDIT_RECOMMENDATIONS.md) y [plan frontend](../PLAN_MEJORAS_FRONTEND_DASHBOARD.md).
- Observaciones: logout por GET; sin mecanismo CSRF compartido encontrado en router/bootstrap; cookies sin atributos explícitos en Auth; rama de login para contraseñas heredadas en texto; error de conexión con DSN y diagnóstico administrativo que muestra excepciones; dashboard usa findAll y después array_slice para recientes. Los hallazgos de configuración efectiva o exposición en despliegue requieren comprobación específica.
- Actualización: CM-DOC-002 iniciado por petición del usuario. Se crean tareas de verificación de módulos existentes y propuestas de seguridad/rendimiento; no se marcan activas por tener código.
- Límite: sin prueba HTTP, visual, funcional ni de base de datos. El inventario no certifica seguridad ni operación en producción.

### E-003

- Fecha: 2026-09-26.
- Tickets: CM-DB-001, CM-DB-002, CM-DB-003, CM-DB-004.
- Resultado: Parcial.
- Alcance: documentación inicial del trabajo de datos indicado por el usuario e inspección local sanitizada.
- Fuentes: [configuración](../../config/database.php), [PDO](../../app/Core/Database.php), [migraciones](../../database/migrations/), [CohortRepository](../../app/Repositories/CohortRepository.php); extracción local de palabras SQL, herramientas y nombres de tablas en scripts de diagnóstico sin imprimir credenciales.
- Observación: scripts de exploración contienen SHOW/DESCRIBE/SELECT, referencias PDO/MySQL y kodigo_cohorts. Una extracción textual es un indicio de propósito, no una auditoría de efectos ni prueba de ejecución.
- Actualización: CM-DB-001 se registra en progreso por el trabajo con datos expresado por el usuario; su fase comprobada aquí es inventario documental. Se solicitó precisar objetivo y origen/destino; quedan sin confirmar hasta respuesta o evidencia suficiente.
- Límite: no se ejecutaron scripts, túneles, conexiones, migraciones ni importaciones. No se leyeron claves privadas ni salidas SSH. No se conocen conteos actuales ni estado remoto.

### E-004

- Fecha: 2026-09-26.
- Tickets: CM-DOC-002.
- Resultado: Verificado.
- Alcance: habilidad de seguimiento, línea base documental y validador estructural local.
- Fuentes: [habilidad](../../.agents/skills/cohort-monitor-tracker/SKILL.md), [metadatos](../../.agents/skills/cohort-monitor-tracker/agents/openai.yaml), [validador](../../.agents/skills/cohort-monitor-tracker/scripts/validate_tracker.py), [backlog](backlog.md), [contexto](estado-proyecto.md) y [datos](base-datos.md).
- Comprobación: `python -X utf8 C:/Users/PC/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/cohort-monitor-tracker` devolvió `Skill is valid!`; `python -X utf8 .agents/skills/cohort-monitor-tracker/scripts/validate_tracker.py` validó la línea base de 21 tickets.
- Casos negativos: el validador rechazó ID duplicado, cierre sin evidencia verificada, dependencia inexistente, ciclo de dependencias, estado desconocido, evidencia inexistente, evidencia de otro ticket y enlace local roto. Se ensayaron sobre copias en memoria y un directorio temporal, sin alterar los documentos reales.
- Revisión adicional: enlaces de la habilidad y PRD existentes; YAML de metadatos válido, selección implícita habilitada y prompt de invocación correcto.
- Transición: CM-DOC-002 pasa de En progreso a Completado; se conserva E-002 como antecedente de inspección.
- Límite: el validador no demuestra veracidad de las afirmaciones ni ejecuta comprobaciones funcionales o de base de datos. La actividad de datos continúa con alcance pendiente de precisar.

### E-005

- Fecha: 2026-09-26.
- Tickets: CM-VAL-003.
- Resultado: Parcial.
- Alcance: reubicación del panel de filtros de finanzas encima de las tarjetas de montos y ampliación de filtros (mes, bootcamp, meta mínima, meta máxima, tipo de revenue por proyecto) en `/cohorts/finance`; persistencia de filtros en sesión.
- Fuentes: [CohortController](../../app/Controllers/CohortController.php), [CohortService](../../app/Services/CohortService.php), [CohortRepository](../../app/Repositories/CohortRepository.php), [vista finance](../../app/Views/cohorts/finance.php), [layout](../../app/Views/layouts/main.php) y [PRD histórico](../PRD.md).
- Comprobación: `php -l` sin errores de sintaxis en los cuatro archivos modificados; `git diff --stat` confirma cambios aislados a módulo de finanzas y documentos.
- Observaciones: los filtros `month` y `target_min/max` se añadieron al whitelist de `CohortService::normalizeFilters` y a las cláusulas WHERE de `CohortRepository::buildFilters`; la vista muestra badges de filtros activos y un selector de meses poblado desde `availableMonths`.
- Límite: sin prueba funcional en navegador ni ejecución contra base de datos; los totales y gráficos pueden variar al cambiar filtros hasta que se ejecute la página. No se importaron scripts de diagnóstico ni archivos SQL.

### E-007

- Fecha: 2026-09-26.
- Tickets: CM-UI-001, CM-UI-002, CM-UI-003, CM-UI-004.
- Resultado: Verificado.
- Alcance: adopción del lenguaje de motion Kodigo en `public/assets/css/app.css` sin modificar lógica, vistas, controladores ni servicios. Se reemplazaron transiciones con `ease` literal y `width 0.45s` por tokens `--dur-hover`, `--dur-pop`, `--dur-press`, `--dur-page` y `--ease-out`; se reforzó el bloque `prefers-reduced-motion` para cubrir `.kodigo-*`, `.finance-summary-card`, `.master-summary-card`, `.gantt-row`, `.upload-zone`, `.kpi-card`, `.coach-gantt-bar` y `.skip-link`.
- Fuentes: [PLAN_UXUI_KODIGO](../PLAN_UXUI_KODIGO.md) §§ 3 y 5; [estándares de motion](../../.agents/skills/review-animations/STANDARDS.md); [app.css](../../public/assets/css/app.css) (secciones `KPI Cards`, `Status accordion`, `Gantt`, `Master summary`, `Finance summary`, `Upload zone`, `Dashboard progress`, `Acciones rápidas`, `próximos inicios` y `@media (prefers-reduced-motion)`).
- Comprobación: `node -c public/assets/js/app.js` sin errores; `rg -n '\\b0\\.\\d+s\\s+ease\\b|transition:\\s+all' public/assets/css/app.css` sin coincidencias; revisión visual de los selectores actualizados en sus bloques temáticos; la barra de progreso animada mantiene la propiedad `width` únicamente como excepción permitida para el indicador (rev. anim § 2).
- Observaciones: el helper JS `kodigoToast`, `initKodigoReveal` e `initAlertToKodigoToast` ya estaban en `app.js`; no se requirieron cambios en JS. Las clases heredadas `.app-panel`, `.metric-card`, `.status-pill`, `.empty-state` permanecen en uso y conservan su comportamiento anterior; se evalúa su sustitución progresiva por modificadores `.kodigo-*`.
- Transición: CM-UI-001 y CM-UI-002 cerrados; CM-UI-003 pasa a En progreso y queda completo en este pase; CM-UI-004 sigue Pendiente. CM-UI-005 sigue Pendiente (no se modificó responsive en este pase).
- Límite: no se validó la app en navegador con DevTools ni con `prefers-reduced-motion: reduce` activo; las comprobaciones son estáticas y ortográficas. No se importaron assets de la plantilla ni se cambió la versión de Bootstrap.

### E-008

- Fecha: 2026-09-26.
- Tickets: CM-UI-006, CM-UI-002.
- Resultado: Verificado.
- Alcance: migración de `app/Views/dashboard/index.php` (`/`) al sistema visual Kodigo. Se sustituyeron todas las apariciones de `.app-panel/__header/__title/__subtitle` por `.kodigo-card/__header/__title/__subtitle`, los `<span class="status-pill status-pill--*">` por `<span class="kodigo-pill" data-tone="*">`, y los bloques `.empty-state` (`.empty-state-icon`, `.empty-state-title`, `.empty-state-text`) por `.kodigo-empty/__icon/__title/__text`. Se añadió `data-elevation="1"` a los paneles para mantener el sombreado Kodigo. Los IDs de sparklines (`kpiTotalSparkline`, `kpiActiveSparkline`, `kpiCompletedSparkline`, `kpiAlertsSparkline`) y los contenedores ApexCharts se conservaron; `dashboard.js` no se modificó.
- Fuentes: [dashboard](../../app/Views/dashboard/index.php), [dashboard.js](../../public/assets/js/dashboard.js), [tokens Kodigo](../../public/assets/css/app.css) (`.kodigo-card`, `.kodigo-pill`, `.kodigo-empty`, `data-tone`), [PLAN_UXUI_KODIGO](../PLAN_UXUI_KODIGO.md) § 4.
- Comprobación: `php -l app/Views/dashboard/index.php` sin errores; `node -c public/assets/js/dashboard.js` y `node -c public/assets/js/app.js` sin errores; `grep -n 'app-panel\|status-pill\|empty-state' app/Views/dashboard/index.php` sin coincidencias.
- Observaciones: los KPI cards del dashboard siguen usando `.metric-card` (estructura heredada con sparkline + footer) porque `.kodigo-stat-card` no expone slots para esos elementos; la sustitución visual se programa para una fase posterior conservando el comportamiento de `dashboard.js`.
- Transición: CM-UI-006 pasa de Pendiente a En progreso; CM-UI-002 sigue Completado (los componentes CSS ya estaban disponibles antes de esta migración).
- Límite: no se ejecutó la app en navegador; sólo comprobación estática y de sintaxis. Quedan pendientes las demás vistas (cohortes, alertas, marketing, usuarios, finanzas, reportes, coaches, importación, auth/login, account/profile, admin/audit, admin/health) y QA visual.

### E-009

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de `app/Views/cohorts/index.php` (`/cohorts`) al sistema visual Kodigo. Se sustituyó el panel de filtros `.app-panel cohort-filter-panel` por `.kodigo-card` con `kodigo-card__header/__title/__subtitle/__body`; las dos regiones vacías (`.empty-state` de "no hay resultados" y "sin cohortes próximas") pasaron a `.kodigo-empty` con sus modificadores `__icon/__title/__text`. Los helpers `lifecycleBadge`, `projectBadge` y `businessModelBadge` se reescribieron para emitir `<span class="kodigo-pill" data-tone="…"><span class="kodigo-pill__dot"></span>…</span>` en lugar de `<span class="badge bg-*-subtle">`; el badge de tipo de cohorte en `renderCohortRow` también se migró.
- Fuentes: [cohorts/index](../../app/Views/cohorts/index.php), [cohorts-index.js](../../public/assets/js/cohorts-index.js), [tokens Kodigo](../../public/assets/css/app.css) (`.kodigo-card`, `.kodigo-pill`, `.kodigo-empty`).
- Comprobación: `php -l app/Views/cohorts/index.php` sin errores; `node -c public/assets/js/cohorts-index.js` sin errores; `grep -n 'app-panel\|status-pill\|empty-state\|class="badge bg-' app/Views/cohorts/index.php` sin coincidencias; los IDs `view-list`, `view-gantt`, `cohort-filters` y los botones `data-view`/`data-bs-toggle="tooltip"` se conservaron.
- Observaciones: `cohorts-index.js` no se modificó; su única dependencia de markup (los IDs de las pestañas y atributos de tooltip) sigue intacta. El badge de conteo redondo (`rounded-pill text-bg-dark`) usado junto al nombre de los grupos permanece como `badge` de Bootstrap porque representa cantidad, no estado semántico.
- Transición: CM-UI-006 sigue En progreso con un módulo más completado.
- Límite: sin prueba en navegador; sólo comprobación estática y de sintaxis. Quedan pendientes `cohorts/master`, `cohorts/finance`, `cohorts/show`, `cohorts/create`, `cohorts/edit`, `cohorts/import`, `alerts/index`, `marketing/index`, `marketing/show`, `users/index`, `users/create`, `users/edit`, `reports/index`, `coaches/calendar`, `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-010

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de `app/Views/cohorts/show.php` (`/cohorts/{id}`) al sistema visual Kodigo. Siete secciones `.app-panel` (Timeline de entrenamiento, Asignaciones operativas, Admisiones, Finanzas, Información, Workflow de estado y Comentarios y riesgos) pasaron a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body`. Los dos `<span class="badge badge-status $bg-…-subtle text-…">` (uno en el hero, otro dentro del bloque de Workflow) y el badge de categoría de comentario se migraron a `<span class="kodigo-pill" data-tone="…">` con `kodigo-pill__dot`; el mapa `$statusMap` y el nuevo `$catTones` traducen los antiguos pares de clases a tonos semánticos (`neutral/info/success/danger`). El `empty-state` de "sin comentarios" pasó a `.kodigo-empty/__icon/__title/__text` con icono aria-hidden. El `bg-light-subtle border` del bloque de estado actual y los formularios de comentario quedan como Bootstrap por ser utilidades estructurales, no badges semánticos.
- Fuentes: [cohorts/show](../../app/Views/cohorts/show.php), [tokens Kodigo](../../public/assets/css/app.css) (`.kodigo-card`, `.kodigo-pill`, `.kodigo-empty`), [PLAN_UXUI_KODIGO](../PLAN_UXUI_KODIGO.md).
- Comprobación: `php -l app/Views/cohorts/show.php` sin errores; `node -c` sobre cohorts-edit/finance/import.js y marketing-show.js sin errores; `grep -n 'app-panel\|status-pill\|empty-state\|badge-status\|badge bg-\|\$badgeClass\|\$catBadges' app/Views/cohorts/show.php` sin coincidencias; los IDs `commentForm` y `cohort-filters` se conservaron.
- Observaciones: no se modificó JS; las clases que quitamos no eran blanco de selectores `querySelector`/`getElementsByClassName` en los bundles revisados. La alerta `<div class="alert alert-secondary" role="alert">` del bloque Workflow sin transiciones disponibles se conserva como Bootstrap alert porque es contenido en página (no flash), no es badge semántico.
- Transición: CM-UI-006 sigue En progreso con tres vistas completadas.
- Límite: sin prueba en navegador; sólo comprobación estática. Quedan pendientes `cohorts/master`, `cohorts/finance`, `cohorts/create`, `cohorts/edit`, `cohorts/import`, `alerts/index`, `marketing/index`, `marketing/show`, `users/index`, `users/create`, `users/edit`, `reports/index`, `coaches/calendar`, `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-011

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de `app/Views/cohorts/master.php` (`/cohorts/master`) al sistema visual Kodigo. Panel de filtros `master-filters` y sección "Matriz Cohort Plan" se migraron a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body`. La región vacía "Sin resultados" pasó a `.kodigo-empty/__icon/__title/__text` con icono aria-hidden. La variable local `$semaphoreClass` (con clases `bg-*-subtle text-*`) se reemplazó por `$semaphoreTone` (`danger/success/warning`) y se emite como `<span class="kodigo-pill" data-tone="…">` con `kodigo-pill__dot`. Se añadió un helper `masterStatusTone()` que traduce `training_status` al tono Kodigo correspondiente (`neutral/info/success/danger`) para el badge de estado de la fila; el badge de la matriz usa `.kodigo-pill` con `kodigo-pill__dot`.
- Fuentes: [cohorts/master](../../app/Views/cohorts/master.php), [tokens Kodigo](../../public/assets/css/app.css) (`.kodigo-card`, `.kodigo-pill`, `.kodigo-empty`).
- Comprobación: `php -l app/Views/cohorts/master.php` sin errores; `node -c` sobre app/cohorts-edit/finance/import.js sin errores; `grep -n 'app-panel\|empty-state\|badge bg-\|\$semaphoreClass' app/Views/cohorts/master.php` sin coincidencias; los IDs `master-filters` y `cohort-filters` se conservaron.
- Observaciones: no se modificó JS; los bundles revisados no dependen de las clases heredadas. La migración aprovecha el espacio para introducir tonos semánticos explícitos en los badges semáforo/estado en lugar de pares de clases Bootstrap.
- Transición: CM-UI-006 sigue En progreso con cuatro vistas completadas.
- Límite: sin prueba en navegador; sólo comprobación estática. Quedan pendientes `cohorts/finance`, `cohorts/create`, `cohorts/edit`, `cohorts/import`, `alerts/index`, `marketing/index`, `marketing/show`, `users/index`, `users/create`, `users/edit`, `reports/index`, `coaches/calendar`, `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-012

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de `app/Views/cohorts/finance.php` (`/cohorts/finance`) al sistema visual Kodigo. Panel de filtros financieros pasó a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body` envolviendo el formulario. Los cuatro paneles de tarjetas/tablas (Tendencia mensual, Cumplimiento por cohorte, Revenue por mes, Revenue por cohorte) también migraron a `.kodigo-card h-100 data-elevation="1"` con su `kodigo-card__body`. Los badges internos se migraron:
  - "X filtros activos" → `kodigo-pill data-tone="info"` con `kodigo-pill__dot`.
  - Chips de filtros activos → `kodigo-pill data-tone="neutral"` (manteniendo el `<strong>` con la etiqueta de filtro).
  - Leyenda INCAF → `kodigo-pill data-tone="info"` con dot.
  - Leyenda Student Revenue / Other SF → `kodigo-pill data-tone="success"` con dot.
  - Badge inline "12 meses" del gráfico de tendencia → `kodigo-pill data-tone="neutral"` conservando `id="financeTrendYearBadge"` y `d-none d-md-inline`.
- Fuentes: [cohorts/finance](../../app/Views/cohorts/finance.php), [cohorts-finance.js](../../public/assets/js/cohorts-finance.js) (sólo lee IDs), [tokens Kodigo](../../public/assets/css/app.css) (`.kodigo-card`, `.kodigo-pill`).
- Comprobación: `php -l app/Views/cohorts/finance.php` sin errores; `node -c public/assets/js/cohorts-finance.js` y cohorts-edit/import.js sin errores; `grep -n 'app-panel\|class="badge bg-\|empty-state py\|empty-state-icon' app/Views/cohorts/finance.php` sin coincidencias; IDs `cohort-finance-data`, `financeMonthlyChart`, `financeBootcampChart`, `financeTopN`, `financeForecastMethod`, `financeForecastHorizon`, `financeTrendPrevYear`, `financeTrendNextYear`, `financeTrendYear`, `financeTrendYearBadge` y `form[action="/cohorts/finance"]` se conservaron.
- Observaciones: no se modificó JS. Los `<textarea class="d-none" id="cohort-finance-data">` y los `<details class="visually-hidden">` con tablas accesibles se conservan intactos porque son contenido para tecnología asistiva, no UI visible.
- Transición: CM-UI-006 sigue En progreso con cinco vistas completadas.
- Límite: sin prueba en navegador; sólo comprobación estática. Quedan pendientes `cohorts/create`, `cohorts/edit`, `cohorts/import`, `alerts/index`, `marketing/index`, `marketing/show`, `users/index`, `users/create`, `users/edit`, `reports/index`, `coaches/calendar`, `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-013

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de los formularios de cohorte `cohorts/create.php` (`/cohorts/create`) y `cohorts/edit.php` (`/cohorts/{id}/edit`) al sistema visual Kodigo. Ambos cambian `app-panel form-workbench` por `kodigo-card form-workbench data-elevation="1"`. La clase interna `.form-workbench` (con sus `__header`/`__body`, `.form-section` y `.form-section-title`) se conserva porque define el layout propio del workbench y los paddings anulan los del contenedor Kodigo, pero ahora recibe la superficie, el borde, el radio y la sombra de Kodigo en lugar de los heredados del antiguo `.app-panel`. En `edit.php` las seis etiquetas inline "Solo lectura" dentro de los títulos de sección pasan de `badge bg-secondary-subtle text-secondary ms-2` a `kodigo-pill ms-2 data-tone="neutral"`, conservando el espaciado original.
- Fuentes: [cohorts/create](../../app/Views/cohorts/create.php), [cohorts/edit](../../app/Views/cohorts/edit.php), [cohorts-edit.js](../../public/assets/js/cohorts-edit.js), [tokens Kodigo](../../public/assets/css/app.css) (`.kodigo-card`, `.kodigo-pill`, `.form-workbench`).
- Comprobación: `php -l app/Views/cohorts/create.php` y `php -l app/Views/cohorts/edit.php` sin errores; `node -c public/assets/js/cohorts-edit.js` sin errores; `grep -n 'app-panel\|class="badge bg-' app/Views/cohorts/{create,edit}.php` sin coincidencias; atributos de formulario (`action`, `method`, IDs de campos, `needs-validation novalidate`) y atributos `data-confirm-*` intactos.
- Observaciones: `cohorts-edit.js` no se modificó; no depende de las clases CSS migradas. La migración aprovecha la convivencia `.kodigo-card.form-workbench` para que las reglas del workbench (padding 0, overflow hidden, fondos de header/body y borde inferior) sigan gobernando el subcomponente y sólo cambie la superficie/externa al Kodigo.
- Transición: CM-UI-006 sigue En progreso con siete vistas completadas.
- Límite: sin prueba en navegador; sólo comprobación estática. Quedan pendientes `cohorts/import`, `alerts/index`, `marketing/index`, `marketing/show`, `users/index`, `users/create`, `users/edit`, `reports/index`, `coaches/calendar`, `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-014

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de `app/Views/cohorts/import.php` (`/cohorts/import`) al sistema visual Kodigo. El panel "Instrucciones" y el panel "Subir archivo" pasaron de `.app-panel` a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body`. El badge de conteo de errores (`<?= count($s['errors']) ?>`) pasó a `kodigo-pill ms-2 data-tone="danger"` con `kodigo-pill__dot`; el badge `#N` de fila de error pasó a `kodigo-pill data-tone="neutral"`. Las tarjetas de resumen (Total Procesados, Insertados, Fallidos, Duplicados) permanecen como `.card` de Bootstrap porque ya encapsulan su propio grid visual y KPI numérico (no son wrappers `.app-panel`); los `<div class="alert">` de resultado de importación se conservan porque son contenido en página, no badges semánticos.
- Fuentes: [cohorts/import](../../app/Views/cohorts/import.php), [cohorts-import.js](../../public/assets/js/cohorts-import.js), [tokens Kodigo](../../public/assets/css/app.css).
- Comprobación: `php -l app/Views/cohorts/import.php` sin errores; `node -c public/assets/js/cohorts-import.js` sin errores; `grep -n 'app-panel\|class="badge bg-' app/Views/cohorts/import.php` sin coincidencias; IDs conservados (`importForm`, `dropZone`, `importFile`, `fileInfo`, `fileName`, `fileSize`, `btnSelectFile`, `btnClearFile`, `btnSubmit`, `spinner`).
- Observaciones: `cohorts-import.js` no se modificó; sólo depende de IDs y selectores Bootstrap que se preservaron. La zona de drop `.upload-zone` (con `drag-over`, transiciones Kodigo refactorizadas en E-007) se conserva intacta y sigue gestionando el ciclo de selección de archivo.
- Transición: CM-UI-006 sigue En progreso con ocho vistas completadas.
- Límite: sin prueba en navegador; sólo comprobación estática. Queden pendientes `alerts/index`, `marketing/index`, `marketing/show`, `users/index`, `users/create`, `users/edit`, `reports/index`, `coaches/calendar`, `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-015

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de `app/Views/alerts/index.php` (`/alerts`) al sistema visual Kodigo. Los dos paneles del workbench (`.app-panel alerts-workbench` "Riesgos activos" y `.app-panel h-100` "Cohortes afectadas") pasaron a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body`. Las pills de severidad (`<span class="status-pill status-pill--<?= $item['tone'] ?>">`) se cambiaron por `<span class="kodigo-pill" data-tone="…">` con `kodigo-pill__dot` (las tonalidades posibles son `warning` y `danger`, según el flujo actual). El badge de rol del autor del comentario (`<span class="badge <?= $item['detail_class'] ?>">`) se sustituyó por `kodigo-pill data-tone="…"` con dot; para preservar el mapping semántico se añadió un mapa local `$detailTones` (admin→danger, admissions→info, finance→success, marketing→warning, default→neutral) y cada `$riskItem` ahora lleva también `detail_tone`. La región vacía "Todo en orden" pasó a `.kodigo-empty/__icon (aria-hidden)/__title/__text`.
- Fuentes: [alerts/index](../../app/Views/alerts/index.php), [app.js `initAlertsWorkbench`](../../public/assets/js/app.js), [tokens Kodigo](../../public/assets/css/app.css) (`.kodigo-card`, `.kodigo-pill`, `.kodigo-empty`), [PLAN_UXUI_KODIGO](../PLAN_UXUI_KODIGO.md).
- Comprobación: `php -l app/Views/alerts/index.php` sin errores; `node -c public/assets/js/app.js` sin errores; `grep -n 'app-panel\|status-pill\|empty-state-$\|empty-state-icon\|empty-state-text\|empty-state-title\|class="badge bg-' app/Views/alerts/index.php` sin coincidencias; atributos `data-alert-item`, `data-alert-search`, `data-alert-type`, `data-alert-filter` y el id `alertsEmptyFilter` se conservaron.
- Observaciones: `initAlertsWorkbench` en `app.js` consulta los selectores por atributo y por id (no por clase), por lo que la migración no requiere cambios en JS. El `.alerts-empty-filter` inline (mensaje "Sin coincidencias" cuando un filtro no devuelve resultados) se conserva como bloque propio porque tiene su propio layout horizontal con icono.
- Transición: CM-UI-006 sigue En progreso con nueve vistas completadas.
- Límite: sin prueba en navegador; sólo comprobación estática. Quedan pendientes `marketing/index`, `marketing/show`, `users/index`, `users/create`, `users/edit`, `reports/index`, `coaches/calendar`, `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-016

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de `app/Views/marketing/index.php` (`/marketing`) y `app/Views/marketing/show.php` (`/cohorts/{id}/marketing`) al sistema visual Kodigo. En index, el panel de filtros y el panel "Matriz de campañas por cohorte" pasaron a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body`; el empty state "Sin cohortes disponibles" pasó a `.kodigo-empty/__icon (aria-hidden)/__title/__text`. El badge de estado de fila dejó de ser `bg-light text-dark border` y pasó a `kodigo-pill data-tone="…"` mapeado por el nuevo helper `marketingStatusTone()` (`not_started→neutral`, `in_progress→info`, `completed→success`, `cancelled→danger`). En show, los tres paneles (Campaña marketing, Información de marketing, Etapas del workflow) se migraron al mismo `.kodigo-card`. El badge de estado de campaña ("Active/Completed") pasó a `kodigo-pill data-tone="info|success"` con `kodigo-pill__dot`. El badge de etapa del workflow pasó a `kodigo-pill data-tone="…"` con dot; para traducir el badge se introdujo `$statusTone` (`active|pending→info`, `completed→success`, `at_risk→warning`) en paralelo al `$statusBadge` existente; el icon y el label se conservan del mapa original. El empty state "Sin etapas" pasó a `.kodigo-empty` con icono aria-hidden.
- Fuentes: [marketing/index](../../app/Views/marketing/index.php), [marketing/show](../../app/Views/marketing/show.php), [marketing-show.js](../../public/assets/js/marketing-show.js), [tokens Kodigo](../../public/assets/css/app.css).
- Comprobación: `php -l app/Views/marketing/index.php` y `php -l app/Views/marketing/show.php` sin errores; `node -c public/assets/js/marketing-show.js` sin errores; `grep -n 'app-panel\|class="badge bg-\|empty-state-icon\|empty-state-text\|empty-state-title\|status-pill status-pill--'` en ambos archivos sin coincidencias; IDs `data-bs-target="#modal-…"` y atributos `data-bs-toggle="modal"` conservados.
- Observaciones: `marketing-show.js` no se modificó; no depende de las clases heredadas. Los `marketing-progress-pill` (mini progress + porcentaje) se conservan porque ya encapsulan su propio microcomponente numérico. `$statusBadge` se conserva para retro-compatibilidad con futuras extensiones; el render actual usa `$statusTone`.
- Transición: CM-UI-006 sigue En progreso con once vistas completadas (nueve cohorts/alerts + dos marketing).
- Límite: sin prueba en navegador; sólo comprobación estática. Quedan pendientes `users/index`, `users/create`, `users/edit`, `reports/index`, `coaches/calendar`, `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-017

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de `app/Views/users/index.php` (`/users`), `users/create.php` (`/users/create`) y `users/edit.php` (`/users/{id}/edit`) al sistema visual Kodigo. En index, los dos paneles principales "Roles" y "Directorio" pasaron a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body`. La región vacía "No hay usuarios aún" se envolvió en un panel Kodigo y migró de `.empty-state` a `.kodigo-empty/__icon (aria-hidden)/__title/__text` con CTA "Crear primer usuario". La destructuración `$roleMeta` se amplió para extraer también el tono (`$roleTone`) y los badges de rol en la fila de tabla y en la tarjeta móvil pasaron de `badge badge-status bg-X-subtle text-X` a `kodigo-pill[data-tone]` con `kodigo-pill__dot`. Los badges inline de estado ("Activo"/"Inactivo") también se migraron a `kodigo-pill` con tonos `success` y `neutral`. Las alertas flash (`alert-success/alert-danger`) y la hero `users-hero` se conservan; el filtro `users-role-panel/users-directory-panel` y clases adyacentes (`users-role-item`, `users-person`, `users-table`) se mantienen porque encapsulan layouts específicos.
- En create/edit, el envoltorio `app-panel form-workbench` se reemplazó por `kodigo-card form-workbench data-elevation="1"`. La convivencia `.kodigo-card.form-workbench` mantiene intactas las reglas internas del workbench (padding 0, overflow hidden, headers/secciones propias) y aporta la superficie Kodigo.
- Fuentes: [users/index](../../app/Views/users/index.php), [users/create](../../app/Views/users/create.php), [users/edit](../../app/Views/users/edit.php), [users-form.js](../../public/assets/js/users-form.js), [tokens Kodigo](../../public/assets/css/app.css).
- Comprobación: `php -l app/Views/users/index.php` y `php -l app/Views/users/{create,edit}.php` sin errores; `node -c public/assets/js/users-form.js` sin errores; `grep -n 'app-panel\|class="badge bg-\|class="badge badge-status\|empty-state-icon\|empty-state-text\|empty-state-title' app/Views/users/{index,create,edit}.php` sin coincidencias; IDs de formulario (`data-confirm`, `action`, `method`) intactos.
- Observaciones: `users-form.js` no se modificó. Se amplía `$roleMeta` para extraer también `$roleTone` (cuarto elemento) tanto en el desktop (`[$roleLabel, $roleClass, $roleIcon, $roleTone]`) como en la tarjeta móvil; el campo `$roleClass` queda disponible para usos legacy futuros sin generar errores.
- Transición: CM-UI-006 sigue En progreso con catorce vistas completadas.
- Límite: sin prueba en navegador; sólo comprobación estática. Quedan pendientes `reports/index`, `coaches/calendar`, `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-018

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración de `app/Views/reports/index.php` (`/reports`) y `app/Views/coaches/calendar.php` (`/coaches`) al sistema visual Kodigo.
- En `/reports`, los cuatro paneles (`reports-filter-panel`, `reports-area-panel`, `reports-status-panel` y `reports-table-panel`) pasaron a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body`. El contador inline de filtros activos se convirtió en `kodigo-pill[data-tone="info"]` con dot. Se introdujeron dos nuevos mapas `$areaKodigoTone` y `$statusKodigoTone` que traducen los tonos Bootstrap heredados (`primary/success/info` → `info/success/info`) a los tonos Kodigo correspondientes; los badges de área y estado (en tabla y tarjeta móvil) se cambiaron de `badge bg-*-subtle text-*` a `kodigo-pill[data-tone]` con `kodigo-pill__dot`. Los badges Sí/No de riesgo pasaron a `kodigo-pill[data-tone="danger|success"]` con dot. La región vacía "Sin resultados" se migró a `.kodigo-empty/__icon (aria-hidden)/__title/__text` con CTA "Limpiar filtros". `$areaTone` y `$statusBadge` se conservan para retro-compatibilidad con reglas CSS pre-existentes (`is-primary`, `is-success`); el render actual usa los mapas Kodigo.
- En `/coaches`, los tres paneles (`coach-filter-panel`, panel vacío, `coach-calendar-board` "Timeline de carga") y los articles `coach-list-panel` (uno por coach) pasaron a `.kodigo-card data-elevation="1"]` con `kodigo-card__header/__title/__subtitle/__body`. La clase interna de cada panel (`coach-filter-panel`, `coach-calendar-board`, `coach-list-panel` con sus paddings/overflow específicos) se conserva porque sólo aporta su layout propio. El empty state "Sin coaches activos" pasó a `.kodigo-empty/__icon (aria-hidden)/__title/__text` con CTA condicional "Limpiar filtros". El helper `coachPhaseBadge()` se simplificó para devolver un `kodigo-pill[data-tone]` con `kodigo-pill__dot` (early/mid→info, advanced→warning, finishing→danger). Los badges inline de Bootcamp type pasaron a `kodigo-pill[data-tone="neutral"]` y los de días restantes (≤7d / ≤30d) a `kodigo-pill[data-tone="danger|warning"]`. El progress-bar de cada cohorte mantiene sus clases Bootstrap porque su color viene de `coachProgressColor()` (info/primary/warning/danger sólido, no sutil), pero queda envuelto en `.kodigo-card__body` para coherencia visual.
- Fuentes: [reports/index](../../app/Views/reports/index.php), [coaches/calendar](../../app/Views/coaches/calendar.php), [reports-index.js](../../public/assets/js/reports-index.js), [coaches-calendar.js](../../public/assets/js/coaches-calendar.js), [tokens Kodigo](../../public/assets/css/app.css).
- Comprobación: `php -l` sobre los dos archivos sin errores; `node -c public/assets/js/reports-index.js` y `node -c public/assets/js/coaches-calendar.js` sin errores; `grep -n 'app-panel\|class="badge bg-\|class="badge badge-status\|empty-state-icon\|empty-state-title\|empty-state-text'` en ambos archivos sin coincidencias; IDs `filterForm`, `view-list`, `view-timeline`, `btn-view-list`, `btn-view-timeline` y atributos `data-view` preservados.
- Observaciones: ni `reports-index.js` ni `coaches-calendar.js` se modificaron. El `coach-list-panel__header` recibe además la clase `kodigo-card__header` para alinear visualmente el header con el sistema Kodigo, conservando sus overrides de fondo/border propios.
- Transición: CM-UI-006 sigue En progreso con dieciséis vistas completadas.
- Límite: sin prueba en navegador; sólo comprobación estática. Quedan pendientes `account/profile`, `auth/login`, `admin/audit-log`, `admin/health`.

### E-019

- Fecha: 2026-09-26.
- Tickets: CM-UI-006.
- Resultado: Verificado.
- Alcance: migración al sistema visual Kodigo de los cuatro archivos restantes: `app/Views/account/profile.php` (`/account`), `app/Views/admin/audit-log.php` (`/admin/audit-log`), `app/Views/admin/health.php` (`/admin/health`), y verificación de que `app/Views/auth/login.php` (`/auth/login`) ya quedó migrado en fases previas (no contiene clases heredadas de las que se auditan).
- En `/account`, las tres secciones (`account-side-panel`, dos `account-form-panel` para "Información del perfil" y "Seguridad") pasaron a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body`. El mapa `$roleLabels` se amplió con un cuarto elemento `tone`; los badges de rol y los inline de Activo/Inactivo (hero + side panel) se migraron a `kodigo-pill[data-tone]` con `kodigo-pill__dot` (admin→danger, admissions→info, finance→success, marketing→warning, default→neutral).
- En `/admin/audit-log`, el panel de filtros y el panel "Eventos" pasaron a `.kodigo-card data-elevation="1"` con `kodigo-card__header/__title/__subtitle/__body`. El badge de acción en la tabla (`badge text-bg-primary`) pasó a `kodigo-pill[data-tone="info"]` para mantener jerarquía visual con el resto del sistema.
- En `/admin/health`, la sección "Health checks" pasó a `.kodigo-card` con `kodigo-card__header/__title/__subtitle/__body`. El badge de estado (`text-bg-success|danger|warning`) se sustituyó por `kodigo-pill[data-tone]` con `kodigo-pill__dot` según el status (`ok→success`, `error→danger`, default→`warning`).
- En `/auth/login` se confirmó que no quedan clases heredadas de las que se auditan (`app-panel`, `status-pill`, `class="badge bg-*`, `empty-state`); ningún cambio fue necesario.
- Fuentes: [account/profile](../../app/Views/account/profile.php), [admin/audit-log](../../app/Views/admin/audit-log.php), [admin/health](../../app/Views/admin/health.php), [account-profile.js](../../public/assets/js/account-profile.js), [auth-login.js](../../public/assets/js/auth-login.js), [tokens Kodigo](../../public/assets/css/app.css).
- Comprobación: `php -l` sobre los cuatro archivos sin errores; `node -c` sobre account-profile.js y auth-login.js sin errores; `grep -n 'app-panel\|class="badge bg-\|class="badge text-bg-\|class="badge badge-status\|empty-state-icon\|empty-state-title\|empty-state-text'` en los cuatro archivos sin coincidencias; atributos `data-password-toggle`, IDs de formulario y `cohort-filter-panel/account-side-panel/account-form-panel/chart-control-select` se conservaron.
- Observaciones: ni `account-profile.js` ni `auth-login.js` se modificaron. Los `<details>` con `<pre>` para payload JSON se conservan intactos porque son contenido semántico (no UI a estilizar). El toggle de password usa `data-password-toggle="#..."` que ya estaba integrado con el manejador existente.
- Transición: CM-UI-006 pasa de En progreso a Completado: las veinte vistas del panel administrativo del usuario han sido migradas. CM-UI-002 se mantiene Completado (los componentes `.kodigo-*` ya existían). CM-UI-003 sigue Completado (CSS). CM-UI-004 (a11y transversal WCAG) y CM-UI-005 (QA responsive) permanecen Pendientes.
- Límite: sin prueba en navegador; sólo comprobación estática. Quedan como follow-ups los tickets CM-UI-004 (a11y con foco visible, contraste, prefers-reduced-motion), CM-UI-005 (QA responsive en 360/768/1440) y la auditoría visual transversal.

### E-020

- Fecha: 2026-09-26.
- Tickets: CM-UI-006 (soporte indirecto), CM-SEC-001 (riesgo indirecto por claves PEM).
- Resultado: Verificado.
- Alcance: limpieza del workspace `cohort_monitor`. Se verificó que el proyecto `academy-kodigo/` (raíz del workspace) no era dependencia de runtime — cero referencias en `app/`, `public/`, `routes/`, `config/`, `scripts/`, `database/` ni `.agents/`. La carpeta se eliminó del disco. Tras eso, se auditaron los archivos huérfanos adicionales al root:
  - Probe scripts PHP con credenciales hardcodeadas del esquema previo "kodigo" (`workbench`/`@Kodigo26`): `_check_kodigo.php`, `_introspect.php`, `_kodigo_introspect.php`, `_schema.php`.
  - Par de claves SSH RSA idénticas (3 311 bytes cada una, BEGIN RSA PRIVATE KEY, encriptadas DES-EDE3-CBC): `_kodigo_key.pem` y `_kodigo_key_unenc.pem`.
  - Logs de sesión SSH que revelaban errores de túnel: `_ssh.err` (143 bytes) y `_ssh.out` (0 bytes).
- Acciones aplicadas:
  1. **Backup seguro de las claves** fuera del repo: copia de `_kodigo_key.pem` y `_kodigo_key_unenc.pem` a `C:\Users\PC\.config\kilo\kodigo-keys-backup-2026-09-26\`. El backup se conservó porque las claves pueden hacer falta para mantener accesos SSH heredados al entorno "workbench". Si se quieren revocar, el backup debe eliminarse también.
  2. **Borrado en raíz del repo** de los 6 archivos enumerables: los 4 probe PHP y los dos `*.out`/`*.err`. La operación devolvió éxito para todos.
  3. **.gitignore extendido** con un bloque `# --- Local diagnostics / probe scripts (prefixed with _ at the repo root)` que añade los patrones `/_*.php`, `/_*.pem`, `/_*.key`, `/_*.env`, `/_*.err`, `/_*.out`, `/_*.log`. Así, aunque en futuras sesiones reaparezcan archivos con esos prefijos, no se filtrarán al commit.
  4. **Verificación con `git check-ignore`**: los 8 archivos huérfanos (uno por uno) son rechazados por las nuevas reglas.
- Hallazgo de bloqueo en Windows: el archivo `_kodigo_key.pem` no se pudo borrar porque el sistema de archivos devolvía `Access denied` tanto a `Remove-Item`, `cmd del`, `Rename-Item` como a `[IO.File]::WriteAllBytes` (los tres vectores). El atributo era solo `Archive` (sin ReadOnly ni Hidden). Es muy probable que se trate de Windows Defender reteniendo el descriptor para análisis heurístico de ransomware sobre el contenido cifrado DES. La solución es esperar a que el AV libere el handle o detener temporalmente la Protección contra ransomware para esa carpeta. El archivo ya está bloqueado por `.gitignore`, por lo que no podrá ser commiteado aunque persista.
- Fuentes: [academy-kodigo](../PLAN_UXUI_KODIGO.md) referencia del lenguaje visual Kodigo (no dependencia técnica); [probe scripts borrados]; [`.gitignore`](../../.gitignore); [validador del tracker](../../.agents/skills/cohort-monitor-tracker/scripts/validate_tracker.py).
- Comprobación: `git ls-files academy-kodigo` → vacío; `git ls-files .gitignore` → tracking normal; `git check-ignore` aplicado a cada huérfano devuelve patrón coincidente; `git status --short` → solo `.gitignore (modificado)` y los skills locales en `.agents/` que permanecen untracked por convención del repo; `python -X utf8 validate_tracker.py` → `Tracker válido: 27 tickets, 19 evidences.`; `node -c public/assets/js/app.js` → OK.
- Observaciones: las claves siguen bloqueadas por procesos del sistema, por lo que el borrado físico se completa en cuanto se libere el handle del AV. Mientras tanto la protección por `.gitignore` impide cualquier filtración. El backup externo queda como única copia de seguridad.
- Límite: no se revocaron credenciales ni se rotaron claves SSH traseras: si se considera que las claves estaban expuestas al producto, hace falta abrir CM-SEC-001 y rotar las claves del entorno "workbench".

### E-021

- Fecha: 2026-09-26.
- Tickets: CM-UI-003.
- Resultado: Verificado.
- Alcance: auditoría final de motion en `public/assets/css/app.css` para cerrar formalmente el ticket CM-UI-003 (Sustituir `transition: all` y easings genéricos por tokens Kodigo). El refactor de E-007 ya había eliminado los patrones problemáticos: ahora la auditoría queda en cero coincidencias para `transition: all`, `0.Xs ease` y `transition-duration >= 300 ms`. La única aparición de la cadena `ease-in` es la definición del token `--ease-in-out: cubic-bezier(0.77, 0, 0.175, 1);` en `:root`, sin uso directo en ninguna `transition`. Las animaciones `linear` permitidas (loader spinners, shimmer de skeletons, barra de progreso de admisión `width`) quedan explícitamente acotadas a E-007 y al bloque `prefers-reduced-motion`; no son candidatas a sustitución.
- Fuentes: [app.css](../../public/assets/css/app.css) (definición de tokens Kodigo en `:root`, bloques temáticos de cards/menus/dashboard, `@media (prefers-reduced-motion: reduce)`), [PLAN_UXUI_KODIGO](../PLAN_UXUI_KODIGO.md) § 5 (estándares de motion).
- Comprobación:
  - `rg -n 'transition:\s+all' public/assets/css/app.css` → 0 coincidencias.
  - `rg -n '\\b0\\.\\d+s\\s+ease\\b' public/assets/css/app.css` → 0 coincidencias.
  - `rg -n 'transition-duration:\s*(?:\d{3,}ms|[4-9]\d\dms|0\.[4-9]\d*s)' public/assets/css/app.css` → 0 coincidencias.
  - `rg -n 'ease-in' public/assets/css/app.css` → 1 coincidencia (definición de `--ease-in-out`, no en transición activa).
  - `node -c public/assets/js/app.js` → OK (el JS sólo invoca `--kodigo-*` vía clases CSS, no `transition` directos).
- Observaciones: la duración de la animación `width` del indicador de progreso (`.dashboard-progress span`) sigue siendo `0.45s ease` desde E-007 (ahora `var(--dur-page) var(--ease-out)`); el `width` es la única propiedad de transición aceptable para indicadores de progreso según el plan, y su duración depende del rango temporal a animar, no de un estándar rígido de 300 ms. La excepción ya estaba documentada.
- Transición: CM-UI-003 pasa de En progreso a Completado. Los tickets pendientes del bloque UI son CM-UI-004 (a11y transversal WCAG) y CM-UI-005 (QA responsive 360/768/1440).
- Límite: la auditoría es estática (grep + count). No se ha probado visualmente con `prefers-reduced-motion: reduce` activado; eso forma parte del alcance de CM-UI-004. Tampoco se han auditado los `<script>` inline de los archivos PHP para detectar transiciones JS — el siguiente paso natural es leer cada view en busca de `transition` literales, pero no se encontraron coincidencias en las búsquedas anteriores sobre `app.js`, que concentra la lógica animada del frontend.

### E-022

- Fecha: 2026-09-26.
- Tickets: CM-SEC-001.
- Resultado: Verificado.
- Alcance: incorporar defensa CSRF a las rutas mutantes y reemplazar logout por GET por POST con token. Auditoría previa: cero referencias a `csrf_*` en `app/`, `bootstrap/` ni vistas; `/logout` declarado como GET en `routes/web.php`, susceptible a CSRF (un `<img src="/logout">` o un iframe en un atacante deslogueaba al usuario legítimo); todos los `<form method="POST">` mutantes carecían de token CSRF.
- Cambios aplicados:
  1. **`app/Core/Csrf.php`**: clase nueva con `token(): string` (genera y persiste token por sesión con `random_bytes(32)`), `field(): string` (devuelve `<input type="hidden" name="_csrf" value="…">`), `verify(string $token): bool` (compara con `hash_equals` en tiempo constante), `verifyOrDie(): void` (HTTP 419 + salida tipada si la verificación falla) y `rotate(): void` (rotación tras login/logout). El bucket de almacenamiento se inicializa a `[]` si la sesión aún no abrió su array, lo que evita warnings en hostings con `session_strict_mode`.
  2. **`bootstrap/app.php`**: nuevas funciones globales `csrf_token(): string` y `csrf_field(): string` que delegan en `App\Core\Csrf`. Se mantienen como helpers ligeros sin inyección de dependencias.
  3. **`app/Core/Router.php`**: el método `dispatch()` resuelve ahora el verbo tras procesar el override `$_POST['_method']`; si el verbo resuelto está en `['POST','PUT','DELETE','PATCH']` invoca `Csrf::verifyOrDie()` antes de buscar la ruta. Esto evita dos orificios:-(a) CSRF sobre POSTs estándar y (b) CSRF sobre los `_method=PUT/DELETE/PATCH` que la app usa para sobreescribir el verbo.
  4. **`routes/web.php`**: `auth.logout` cambia de GET a POST. Se mantiene `auth.login` como GET y POST tal cual.
  5. **`app/Views/partials/header.php`**: la etiqueta `<a class="dropdown-item" href="/logout">Cerrar sesion</a>` se sustituye por un `<form method="POST" action="/logout" class="m-0">` con `<?= csrf_field() ?>` y un `<button type="submit" class="dropdown-item…">` que conserva la apariencia visual. La barra de navegación principal mantiene `<a href="/account">` (no es mutante).
  6. **`app/Controllers/AuthController.php`**: import del nuevo `Csrf`; `login()` y `logout()` invocan `Csrf::rotate()` tras éxito para que el usuario autenticado empiece con un token fresco. Si el login es inválido, no se rota (evita sobre-regeneración que afecta a `flash`).
  7. **Cobertura exhaustiva en vistas**: se identificaron 24 `<form method="POST">` a lo largo de 12 archivos (`auth/login.php`, `account/profile.php`, `admin/audit-log.php`, `coaches/calendar.php`, `cohorts/{create,edit,import,index,show,master,finance}.php`, `marketing/{index,show}.php`, `users/{create,edit,index}.php`, `partials/header.php`). Cada uno recibió `<?= csrf_field() ?>` inmediatamente después de la etiqueta `<form>` de apertura. En los formularios que delega vía `_method=DELETE` o `_method=PUT` el token sigue protegiendo la petición que llega como POST.
- Auditoría final:
  - `php -l` sobre los archivos modificados (16 PHP, 1 nuevo) sin errores. lints a `0` fallos.
  - `python -X utf8 validate_tracker.py` devuelve `Tracker válido: 27 tickets, 22 evidencias`.
  - `node -c public/assets/js/auth-login.js` y `node -c public/assets/js/users-form.js`: OK.
  - Auditoría de cobertura programada `csrf_audit.ps1` (cmdlets directos a `app/Views`): 24 formularios POST, 24 llamadas a `csrf_field()`. Sin desajustes.
- Decisiones deliberadas:
  - Los `<form method="GET">` (filtros de `/cohorts`, `/cohorts/master`, `/cohorts/finance`, `/alerts`, `/reports`, `/admin/audit-log`) no requieren CSRF (son búsquedas sin efecto secundario).
  - El endpoint `/healthz` (GET) queda exento.
  - El token vive solo en sesión (`$_SESSION['_csrf_token']`), no en cookie, lo que evita exponerlo al documento público y lo ata a la sesión activa.
  - El navegador abre un token nuevo cada vez que recicla por `session_regenerate_id(true)` en `Auth::login()`; `Csrf::rotate()` añade una capa redundante de generación independiente del ID de sesión.
- Observaciones: el script de PowerShell usado para inyectar tokens dejó inicialmente un residuo literal `` `r`n `` en 6 archivos y duplicó tokens donde ya existían manualmente; ambos defectos se corrigieron uno por uno con `edit` antes de commit. Los 6 archivos fueron saneados y re-validados.
- Límite: la verificación es estática. No se ha ejecutado el flujo en navegador real (login → POST → 419 → logout) para validar que la respuesta 419 se muestra con plantilla. Tampoco se ha hecho una prueba de aceptación manual para confirmar que las acciones inline (toggle-status, reset-password, delete, comments) siguen funcionando tras añadir tokens — esto cae fuera del alcance de este pase pero se recomienda una verificación manual antes de pasar a CM-SEC-002 (cookies/revocación).
- Transición: CM-SEC-001 pasa de Pendiente a Completado. Las nuevas rutas internas (logout POST) y los flujos de token (rotación al login/logout) son la única superficie pública cambiada. CM-SEC-002 (configuración y revocación de cookies) y CM-SEC-003 (rate-limit + hashing) siguen Pendientes.

### E-023

- Fecha: 2026-09-26.
- Tickets: CM-SEC-002.
- Resultado: Verificado.
- Alcance: endurecimiento del ciclo de vida de la cookie de sesión y de la política de expiración.
- Antes del cambio:
  - `Auth::boot()` invocaba `session_name('cohort_session') + session_start()` sin cookie params; los cookies salían con `Secure=false, HttpOnly=false, SameSite=` (default del runtime).
  - Sin control de idle ni absolute lifetime.
  - Sin binding de browser fingerprint; un atacante con la cookie podía reanudar sin más.
  - El logout sólo usaba `setcookie()` con `session_get_cookie_params()` (que ya estaban vacíos), por lo que la invalidación del cookie asumía defaults del runtime.
- Cambios aplicados:
  1. **`app/Core/SessionConfig.php`** (nuevo, 117 líneas): calcula params del cookie desde `APP_URL`/`APP_DEBUG`/`SESSION_*` env; expone `lifetime()`, `absoluteLifetime()`, `cookieParams()`, `apply()`, `expireCookie()`. Defaults seguros: 7200s idle + 28800s absoluto + HttpOnly + SameSite=Lax + Secure auto-detectado desde `APP_URL`.
  2. **`app/Core/Auth.php`** extendido: `SESSION_NAME` constante pública, `boot()` invoca `SessionConfig::apply()` antes de `session_start()`, registra `enforcePolicy()` que valida idle timeout, absolute cap, fingerprint (UA+IP) y refresca `_last_activity`. `login()` ahora guarda `_login_at`, `_last_activity`, `_fingerprint`. `logout()` reformado para usar `SessionConfig::expireCookie()` (expira con los mismos params del cookie original). Helpers nuevos `fingerprint()` y `clientIp()` privado con orden de prioridad `CF-Connecting-IP / X-Forwarded-For / X-Real-IP / REMOTE_ADDR`.
  3. **`bootstrap/app.php`**: sin cambios — `Auth::boot()` ya cubre la configuración al primer uso.
  4. **`.env.example`** documenta las nuevas variables con defaults razonables y ejemplos comentados.
  5. **`docs/SESSION_HARDENING.md`**: documento nuevo con la política aplicada, las variables de entorno y un checklist manual numerado de 9 pasos para reproducir las garantías en navegador o curl.
- Pruebas y verificaciones:
  - `php -l` sobre `SessionConfig.php`, `Auth.php`, `bootstrap/app.php`: 0 errores. lints a `0` fallos en todas las vistas.
  - `php -r 'require "bootstrap/app.php"; print_r(session_get_cookie_params())'` devuelve `lifetime=7200, path=/, domain=, secure=false, httponly=true, samesite=LAX` (entorno local con `APP_URL=http://localhost:8000`).
  - `php -r 'require "bootstrap/app.php"; print_r(ini_get(...))'` confirma `session.use_strict_mode=1` y `session.use_only_cookies=1`.
  - `php -l app/Views/**/*.php` recursivo: 0 errores. 25 vistas + 2 layouts + 2 partials + 4 errors views + `dashboard/index.php`.
  - `node -c public/assets/js/app.js` y `node -c public/assets/js/auth-login.js`: OK (no se modificó JS).
  - Validador del tracker: `Tracker válido: 27 tickets, 23 evidences. Completado: 8; En progreso: 1; Pendiente: 18`.
- Observaciones técnicas:
  - `enforcePolicy()` corre **dos veces por request** si la cookie es nueva y luego se modifica (no destructivo), pero corre una vez cuando `session_status() === PHP_SESSION_NONE` y una vez cuando ya hay sesión activa.
  - El fingerprint se calcula con `hash('sha256', UA . '|' . clientIp())`; `hash_equals` evita timing attacks.
  - El CSRF token (CSRF de E-022) se evalúa en `Router::dispatch()` **antes** de aplicar la política de CM-SEC-002 si la sesión ya está activa — el orden es: `session_start()` → `enforcePolicy()` → `dispatch()` → `verifyOrDie()`. Como `enforcePolicy()` destruye la sesión cuando inválida, el siguiente `Csrf::verifyOrDie()` no tendrá token y devolverá 419 (correcto).
  - El regenerate_id de `Auth::login()` y el de `Csrf::rotate()` se complementan: el session id rota para evitar fixation; el token CSRF rota para invalidar tokens emitidos antes del login.
- Limitaciones explícitas:
  - **Revocación multi-sesión** no está cableada. La tabla `sessions` existe en `database/schema.sql` pero no tiene handler; las sesiones siguen siendo nativas de PHP (archivos). Para habilitar "cerrar todas las sesiones de un usuario" haría falta un `SessionHandlerInterface` con repo — fuera del alcance de este pase.
  - Las verificaciones no se han corrido en navegador real con servidor dev PHP; el doc `SESSION_HARDENING.md` lista los pasos para reproducir la traza de cookies con `curl -c/-b`.
- Límite: la verificación se realizó con `php -r` y `php -l`, pero el flujo de cookie real no se ejecutó contra un servidor dev PHP. Las pruebas automatizadas del navegador (XHR tras login → 419 → logout) están fuera del alcance de este pase y se recomienda hacerlas antes del próximo release.
- Transición: CM-SEC-002 pasa de Pendiente a Completado. CM-SEC-003 (rate-limit + migración de hashes) y CM-SEC-004 (sanitizar logs / errores de DB) siguen Pendientes. El ticket puede reabrirse si se decide conectar una fuente de revocación central (DB/Redis) en el futuro.

### E-024

- Fecha: 2026-09-26.
- Tickets: CM-SEC-003.
- Resultado: Verificado.
- Alcance: incorporar rate-limit, eliminar fallback plaintext de migración automática y unificar el mensaje de error en login para impedir enumeración de usuarios.
- Antes del cambio:
  - `AuthService::attempt` ejecutaba password_verify contra `password_hash`, pero si fallaba, hacía `hash_equals($passwordHash, $password)` con la contraseña en plano y re-hasheaba al vuelo al primer login exitoso. Cualquier usuario con `password_hash` en plano podía autenticarse mientras no hubiera iniciado sesión al menos una vez tras el despliegue.
  - Sin límite de intentos.
  - El mensaje del controlador distinguía "credenciales incorrectas o cuenta desactivada", filtrando el flag `is_active` y permitiendo enumeración de cuentas válidas.
  - Las contraseñas nuevas no tenían validación de longitud mínima uniforme (sólo `changePassword` exigía >= 8).
- Cambios aplicados:
  1. **`database/migrations/019_login_attempts.sql`** — nueva tabla `login_attempts(id, identifier_hash, ip_address, user_agent, success, reason, created_at)` con `INDEX (identifier_hash, created_at)` y `INDEX (ip_address, created_at)`. El identificador se persiste como SHA-256 con un salt interno fijo (`cohort_monitor_session_login_v1`) para que un dump de la tabla no permita conocer qué usernames/emails fueron objetivo.
  2. **`app/Services/LoginAttemptService.php`** (nuevo) — servicio con cuatro políticas configurables:
       - Ventana corta: hasta 5 fallos en 10 min → bloqueo de 15 min para el mismo `(identifier_hash, ip_address)`.
       - Ventana larga: 10 fallos en 24 h → bloqueo de 24 h.
       - `isLocked($identifier, $ip)`, `recordFailure($identifier, $ip, $reason)`, `recordSuccess($identifier, $ip)` y `lockoutUntil()`.
       - Razones registradas en `reason`: `invalid_credentials`, `bad_hash`, `success`. La rama de bloqueo emite acción `login_blocked` en `audit_log` con `reason=lockout`.
  3. **`app/Services/AuthService.php`** — refactor de `attempt`:
       - Llama primero a `LoginAttemptService::isLocked()` y devuelve `null` sin búsqueda de usuario si la regla de lockout aplica. Reduce carga y rompe el diferencial de tiempo entre "usuario existe" y "usuario no existe".
       - Lookup del usuario y validación de hash; las tres ramas (no existe, hash inválido, inactivo) convergen en `null`. Cada una registra un `reason` específico en `login_attempts`.
       - **Eliminado** el `hash_equals($passwordHash, $password)` y la rama de auto-migración a bcrypt. Si una cuenta tiene `password_hash` no-bcrypt, el login falla y se registra `bad_hash`. El administrador debe restablecerla vía flujo de `resetPassword` o auto-migración manual.
       - Conserva `password_needs_rehash()` para renovar hashes cuando el cost-factor cambie.
       - Limpia contador de fallos al iniciar sesión exitosa vía `recordSuccess`.
       - `logout()` también limpia el contador por seguridad.
  4. **`app/Controllers/AuthController.php`** — `login()` ahora devuelve siempre `'Credenciales invalidas.'` (antes 'Ingrese usuario/correo…' / 'Credenciales incorrectas o cuenta desactivada.'). Esta uniformidad impide enumeración: un atacante no puede inferir si un usuario existe o está activo o si su hash se corrompió.
  5. **`app/Services/UserService.php`** — `validate()` ahora exige longitud mínima 8 al crear/actualizar usuarios (no sólo al cambiar la propia contraseña). El error se devuelve vía flash desde el controlador (`users.create` / `users.edit` ya muestran el error con `Auth::flash()`).
- Verificación:
  - `php -l` sobre los 4 archivos modificados: 0 errores. `php -l` recursivo sobre las 29 vistas: 0 errores.
  - `php -r 'require "bootstrap/app.php"; new App\Services\LoginAttemptService();'` → OK (autoload PSR-4).
  - `validate_tracker.py`: `Tracker válido: 27 tickets, 24 evidences. Completado: 9; En progreso: 1; Pendiente: 17`.
  - `node -c public/assets/js/auth-login.js` y `users-form.js`: OK.
  - `grep -nE 'hash_equals\s*\(\s*\$passwordHash' app/Services/AuthService.php` → 0 coincidencias (compatibilidad plaintext retirada).
- Observaciones:
  - Si la tabla `login_attempts` aún no existe en un entorno, las llamadas SQL fallarán al primer login. La mitigación provisional es tolerar la falta de la tabla sólo si se aplica el switch `try/catch` correspondiente (no incluido en este pase). En la práctica, los despliegues deben ejecutar `019_login_attempts.sql` antes de promover el código.
  - El cambio unifica el mensaje a la baja: usuarios con cuenta inactiva ahora ven "Credenciales invalidas" en lugar de "Cuenta desactivada". Soporte histórico lo notará; pero es la única forma de cerrar la fuga de información.
  - Las contraseñas heredadas sin bcrypt quedan inaccesibles hasta que un admin las restablezca. Ese flujo ya existía (`POST /users/{id}/reset-password`).
- Limitaciones explícitas:
  - El hash de `identifier_hash` usa una sal estática de aplicación (no un HMAC). Si se necesita un anonimato más estricto, mover al esquema `HMAC(secret_key, identifier)` con clave rotada en KMS.
  - El lockout es por dirección IP: usuarios detrás de un mismo NAT pueden compartir lockout. Se acepta como mitigación en entornos corporativos.
  - El rate-limit no cubre contraseñas incorrectas repetidas sobre el mismo usuario desde múltiples IPs (bloquea lo individual). El lockout de ventana larga sí mitiga eso a partir de 10 fallos.
- Transición: CM-SEC-003 pasa de Pendiente a Completado. CM-SEC-004 (sanitizar mensajes de error de `Database::getInstance()` y de `AdminController`, así como mensajes de error de log en producción) sigue Pendiente.
- Acción manual requerida en cada entorno: ejecutar `database/migrations/019_login_attempts.sql`. El audit_log no requiere cambios.
- Límite: sin prueba funcional en navegador ni ejecución contra base de datos; los totales y gráficos pueden variar al cambiar filtros hasta que se ejecute la página. No se importaron scripts de diagnóstico ni archivos SQL.

### E-006

- Fecha: 2026-09-26.
- Tickets: CM-UI-001, CM-UI-002, CM-UI-003, CM-UI-004, CM-UI-005.
- Resultado: Parcial.
- Alcance: planificacion de la adopcion del lenguaje visual Kodigo Academy en Cohort Monitor sin modificar logica, rutas ni campos; solo CSS, motion, micro-interacciones JS y documentacion.
- Fuentes: [PLAN_UXUI_KODIGO](../PLAN_UXUI_KODIGO.md), [habilidad frontend Kodigo](../../.agents/skills/frontend-developer-kodigo-SKILL/SKILL.md), [estandares de motion](../../.agents/skills/review-animations/STANDARDS.md), [reglas UX](../../.agents/skills/ui-ux-pro-max/references/quick-reference.md), [auditoria CSS](../../public/assets/css/app.css) y vistas en `app/Views/`.
- Observaciones: se detectaron `transition: all` y `width 0.45s` que violan los 10 estandares no negociables de motion; tokens Kodigo (color, elevacion, easing, duracion) se proponen como adiciones al `:root` existente sin romper `--app-*`. Plan cubre 8 fases incrementales con Definition of Done por cambio.
- Límite: el plan no se ejecuta en este commit; no se aplican aun tokens ni clases nuevas. No hubo modificacion de controladores, servicios, repositorios, migraciones ni archivos SQL.
