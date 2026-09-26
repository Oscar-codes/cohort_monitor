# Estado y contexto de Cohort Monitor

Última revisión: 2026-09-26. Alcance: inspección del checkout y documentación; sin sesión funcional de navegador ni conexión a base de datos en esta revisión.

Consultar el [backlog](backlog.md) para estados oficiales y la [bitácora](registro-cambios.md) para evidencia. Esta página resume capacidades y foco; no mantiene una segunda tabla de estados de tickets.

## Producto y arquitectura

Gestión interna de cohortes educativas: admisiones, planificación/ejecución, finanzas, marketing y seguimiento operativo. Stack comprobado en [Composer](../../composer.json), [Dockerfile](../../Dockerfile) y [layout](../../app/Views/layouts/main.php): PHP MVC propio, MySQL/PDO, Bootstrap, Bootstrap Icons, JavaScript, SweetAlert2, ApexCharts, PhpSpreadsheet y Dompdf. Composer declara PHP >=8.1 y Docker usa 8.2; comprobar plataforma efectiva al desplegar.

Flujo esperado: Controller → Service → Repository → PDO/MySQL; vistas PHP para presentación. Hay acoplamientos heredados que deben refinarse por alcance, no confundirse con ausencia de separación arquitectónica.

## Implementación observada

Estas capacidades están representadas en rutas y código. **Implementación observada no equivale a validación integral ni despliegue confirmado.** Ver [E-002](registro-cambios.md#e-002).

| Capacidad | Evidencia principal | Validación asociada |
| --- | --- | --- |
| CRUD, filtros, master, workflow y estado efectivo por fechas | [CohortController](../../app/Controllers/CohortController.php), [CohortService](../../app/Services/CohortService.php), [repositorio](../../app/Repositories/CohortRepository.php) | CM-VAL-001 |
| Login, cuenta, usuarios y roles admin/finance/admissions_b2b/admissions_b2c/marketing | [Auth](../../app/Core/Auth.php), [AuthService](../../app/Services/AuthService.php), [UserService](../../app/Services/UserService.php) | CM-VAL-002 |
| Finanzas, agregaciones y preferencias de gráficos en sesión | [CohortController](../../app/Controllers/CohortController.php), [vista financiera](../../app/Views/cohorts/finance.php) | CM-VAL-003 |
| Etapas e información de marketing, comentarios y alertas | [MarketingService](../../app/Services/MarketingService.php), [AlertService](../../app/Services/AlertService.php), [CommentController](../../app/Controllers/CommentController.php) | CM-VAL-004 |
| Importación Excel/CSV y exportaciones CSV/XLSX/PDF | [CohortImportService](../../app/Services/CohortImportService.php), [ReportService](../../app/Services/ReportService.php), [rutas](../../routes/web.php) | CM-VAL-005 |
| Dashboard, calendario, assets locales e interacción por página | [DashboardService](../../app/Services/DashboardService.php), [CoachCalendarService](../../app/Services/CoachCalendarService.php), [layout](../../app/Views/layouts/main.php) | CM-VAL-006 |
| Auditoría y health administrativo con SELECT 1 e inspección de tablas | [AdminController](../../app/Controllers/AdminController.php), [AuditRepository](../../app/Repositories/AuditRepository.php) | CM-VAL-007 |

## Foco actual y siguiente avance

La petición actual establece seguimiento del proyecto y del trabajo con datos. La habilidad de seguimiento y su línea base documental quedaron creadas y validadas ([E-004](registro-cambios.md#e-004)); la exploración de base de datos indicada por el usuario tiene scripts locales asociados, pero aún falta confirmar su objetivo y sus resultados operativos. Ver [registro de datos](base-datos.md).

Al retomar: consultar CM-DB-001 para confirmar objetivo/origen/destino, después comprobar acceso y esquema según el alcance autorizado. Elegir una validación funcional acotada al siguiente módulo que se vaya a modificar. Las tareas de seguridad están priorizadas en el backlog; su inclusión no significa que se estén implementando en esta sesión.

## Diferencias con documentos históricos

- El [PRD](../PRD.md) y [changelog](../CHANGELOG.md) describen entregas previas, pero no abarcan todo el checkout actual. No usar cantidades históricas de cohortes o tablas como conteos vivos.
- El [plan frontend](../PLAN_MEJORAS_FRONTEND_DASHBOARD.md) describe CDN y gráficos ausentes; el layout actual carga assets locales y hay gráficos en el código. Falta QA funcional/visual para cerrar la modernización completa.
- La [auditoría](../AUDIT_RECOMMENDATIONS.md) es una fuente de candidatos a mejora, no una lista de defectos actuales comprobados en bloque. Por ejemplo, `updatePartial()` hoy delega en un UPDATE de campos fijos; revalidar antes de reproducir el hallazgo histórico sobre SQL dinámico.
- Marketing normaliza `pending`/`at_risk` a `active`, mientras existen consultas relacionadas con riesgo. Comprobar la coherencia del modelo y las alertas antes de afirmar compatibilidad completa.
- El PRD menciona estudiantes y API como futuro. Hay un modelo Student, pero no rutas de CRUD de estudiantes ni un archivo `routes/api.php` en el inventario observado. Son propuestas, no tareas de implementación activas.

## Límites de certeza

No se comprobó qué entorno está desplegado, qué migraciones se aplicaron, cuántas filas existen, si se transfirieron datos entre bases ni si los scripts locales se ejecutaron. No se encontró suite versionada de PHPUnit/PHPStan/Psalm en la revisión inicial. La documentación creada hoy no certifica operación en producción.

Las decisiones de arquitectura y desarrollo están recogidas en [PHP senior](../../.agents/skills/php-senior-cohort-monitor/SKILL.md); las reglas de mantenimiento de estos registros en [tracker](../../.agents/skills/cohort-monitor-tracker/SKILL.md).
