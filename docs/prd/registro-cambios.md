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
- Fuentes: [CohortController](../../app/Controllers/CohortController.php), [CohortService](../../app/Services/CohortService.php), [CohortRepository](../../app/Repositories/CohortRepository.php), [vista finance](../../app/Views/cohorts/finance.php), [layout](../layouts/) y [PRD histórico](../PRD.md).
- Comprobación: `php -l` sin errores de sintaxis en los cuatro archivos modificados; `git diff --stat` confirma cambios aislados a módulo de finanzas y documentos.
- Observaciones: los filtros `month` y `target_min/max` se añadieron al whitelist de `CohortService::normalizeFilters` y a las cláusulas WHERE de `CohortRepository::buildFilters`; la vista muestra badges de filtros activos y un selector de meses poblado desde `availableMonths`.
- Límite: sin prueba funcional en navegador ni ejecución contra base de datos; los totales y gráficos pueden variar al cambiar filtros hasta que se ejecute la página. No se importaron scripts de diagnóstico ni archivos SQL.
