# Seguimiento de base de datos

Última revisión: 2026-09-26. Tickets canónicos: CM-DB-001 a CM-DB-004 en el [backlog](backlog.md). Evidencia inicial: [E-003](registro-cambios.md#e-003).

## Objetivo actual

El usuario indicó trabajo en curso con datos de base de datos. Se observaron scripts locales de exploración MySQL/PDO y referencias a `kodigo_cohorts`. **No se ha confirmado si el objetivo es inspección, importación, migración o sincronización**, ni la dirección de transferencia. Esta revisión solo documentó estructura local; no ejecutó esos scripts ni abrió conexiones.

| Dato de operación | Situación comprobada |
| --- | --- |
| Motor de la aplicación | MySQL mediante PDO en código |
| Configuración soportada | Variables DB_* / MYSQL* y URLs MySQL; valores reales no publicados |
| Entorno activo | No confirmado |
| Origen/destino de datos | No confirmado |
| Acceso SSH/túnel | Hay artefactos locales relacionados; estado y uso no verificados |
| Tabla externa referenciada por un script | kodigo_cohorts; existencia remota no comprobada |
| Tabla operativa consultada por el repositorio | cohorts; esquema real del entorno no comprobado |
| Resultado de conexión/SELECT 1 | No ejecutado en esta revisión |
| Filas actuales o importadas | No medidas; no inferirlas de seeds, CSV o PRD |

## Fuentes locales y esquema esperado

- [Configuración](../../config/database.php) y [wrapper PDO](../../app/Core/Database.php): MySQL/utf8mb4, parámetros preparados y transacciones.
- [CohortRepository](../../app/Repositories/CohortRepository.php): consultas de cohortes, admisiones y finanzas. Debe contrastarse con columnas reales antes de mapear datos.
- [CohortService](../../app/Services/CohortService.php): normalización de estados, hitos y workflow; estado efectivo por fecha puede diferir del guardado.
- [Repositorios](../../app/Repositories/): contratos esperados para usuarios, marketing, comentarios, reportes y auditoría.
- [Migraciones](../../database/migrations/): secuencia observada 002–019 al retomar CM-DB-001; no se identificó aquí un registro de aplicaciones por entorno. Numeración o existencia no prueban ejecución.

| Grupo de archivos | Propósito visible | Límite |
| --- | --- | --- |
| 002–005 | Refactor de cohortes, autenticación/marketing, admisiones B2B y área | No certificados sobre un entorno real |
| 006 y 009 | Seeds históricos de cohortes | Incluyen sustitución/borrado de datos; no usarlos como actualización rutinaria |
| 007–008 | Índices de filtros y corrección de encoding | Falta contraste con esquema/datos actuales |
| 010–013 y 017 | Roles/usuarios y alineación de auditoría | No reproducir ni documentar valores de credenciales de seeds |
| 014–016 | Workflow/comentarios, estados de marketing e información de campañas | Comprobar compatibilidad con servicios actuales |
| 018 | Meta B2C y dos importes financieros en cohorts | El repositorio ya lee y escribe estas columnas; aplicación real pendiente de comprobar |
| 019 | Intentos de login e índices por identificador/IP y fecha | Revisar charset/collation antes de ensayo; aplicación real no comprobada |

Hay varios dumps (`schema.sql`, `dump.sql`, `railway_dump.sql`, `railway_dump_live.sql`). Ninguno se declara copia actual de una base remota por su nombre. No ejecutarlos ni copiar sus filas a esta documentación.

Los scripts `_check_kodigo.php`, `_introspect.php`, `_kodigo_introspect.php` y `_schema.php` estaban sin seguimiento de Git. Solo se extrajeron tipos de consulta y referencias de estructura; no se publicaron sus literales de conexión ni se ejecutaron. Los archivos de claves y salidas SSH no se leyeron. No convertir esos artefactos en dependencia del tracker.

## Reanudación de CM-DB-001: inventario local

El 2026-09-26 se retomó el ticket con inspección documental y de código ([E-028](registro-cambios.md#e-028)). Este es el alcance ejecutado, no una confirmación del objetivo operativo pendiente. Los cuatro scripts citados en E-003 ya no están presentes en la raíz del checkout. Existe `scripts/db-tunnel.ps1`, pero no se ejecutó ni se comprobó un túnel. En `.env` se confirmó únicamente la presencia de los nombres `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` y `DB_CHARSET`; no se publicaron valores ni se validó su efectividad.

### Tablas esperadas por el código actual

Este inventario identifica consumidores locales; no afirma que las tablas existan en un servidor.

| Tabla | Consumidor | Contrato que debe contrastarse |
| --- | --- | --- |
| cohorts | [CohortRepository](../../app/Repositories/CohortRepository.php), [ReportRepository](../../app/Repositories/ReportRepository.php) | id, cohort_code, name, correlative_number; metas y admisiones B2B/B2C; fechas; proyecto, coach, bootcamp, área, horario y training_status; created_at/updated_at |
| users | [UserRepository](../../app/Repositories/UserRepository.php) | Identidad, password_hash, role, is_active, last_login_at y timestamps; inspeccionar estructura sin extraer cuentas |
| audit_log | [AuditRepository](../../app/Repositories/AuditRepository.php) | user_id y entity_key con fallback a entity_id; action, entity_type y timestamps; no volcar old_values/new_values |
| cohort_comments | [CommentRepository](../../app/Repositories/CommentRepository.php) | cohort_id enlazado por consulta con cohorts.id y user_id con users.id; category, body y created_at |
| marketing_stages | [MarketingStageRepository](../../app/Repositories/MarketingStageRepository.php) | cohort_id, stage_name, status, risk_notes y updated_by; relaciones consultadas con cohorts/users |
| cohort_marketing_info | [CohortMarketingInfoRepository](../../app/Repositories/CohortMarketingInfoRepository.php) | upsert por cohort_id; campaign_status y notas de estrategia, contenido, ads, orgánico, eventos, alianzas y analítica |
| login_attempts | [LoginAttemptService](../../app/Services/LoginAttemptService.php) | identifier_hash, ip_address, user_agent, success, reason y created_at; solo metadatos en la primera inspección |

La migración [018](../../database/migrations/018_add_missing_cohort_finance_fields.sql) define `b2c_admission_target INT UNSIGNED` y `financial_target_revenue`/`financial_actual_revenue DECIMAL(12,2)`. Son columnas usadas por el repositorio, no cantidades comprobadas. `training_date_50` y `training_date_75` aparecen como alias NULL en su SELECT y se calculan en la aplicación; no exigirlos como columnas físicas por esos alias.

No se encontraron referencias a `kodigo_cohorts`, `cohort_sections` ni `cohort_finance` en `app/`, `config/` y `routes/`. La mención histórica de `kodigo_cohorts` se conserva como antecedente, sin asignarle equivalencia ni dirección de transferencia.

### Diferencias locales para contrastar en CM-DB-003

- [014](../../database/migrations/014_cohort_workflow_and_change_requests.sql) altera `cohort_sections` y convierte estados a `planned`/`pending_reschedule`; el repositorio actual opera sobre `cohorts` y admite `not_started`, `in_progress`, `completed`, `cancelled`. Falta conocer el esquema efectivo antes de decidir compatibilidad o corrección.
- [016](../../database/migrations/016_add_cohort_marketing_info.sql) contiene un primer CREATE terminado seguido de `EXT NULL,` y un fragmento duplicado con FK a `cohort_sections`. Es un defecto visible del archivo; no demuestra que el servidor tenga esa estructura ni que el script se aplicara íntegro.
- [019](../../database/migrations/019_login_attempts.sql) combina `DEFAULT CHARSET=utf8mb4` con `COLLATE=utf8_unicode_ci` y menciona `psql` pese a usar sintaxis MySQL. Revisar y ensayar en base desechable en el trabajo de compatibilidad; no ejecutar el archivo tal como está como paso automático.

### Información que falta para cerrar CM-DB-001

1. Objetivo operativo: inspección, importación/migración o sincronización.
2. Alias lógico del entorno pertinente (local, pruebas o producción) y cuál configuración lo representa, sin enviar credenciales.
3. Si habrá transferencia, origen, destino y dirección; si solo habrá inspección, registrar destino como «No aplica» una vez confirmado.
4. Confirmar las tablas del alcance: las del inventario operativo y, si corresponde, `kodigo_cohorts`.

Con esa definición, CM-DB-002 podrá comprobar conexión y SELECT 1; CM-DB-003 consultará metadatos de tablas, columnas, índices y relaciones, sin aplicar DDL. Los conteos y reglas de correspondencia se registrarán separadamente en CM-DB-004 cuando proceda.

## Matriz de trabajo por completar

| Tema | Comprobación requerida | Ticket |
| --- | --- | --- |
| Entorno y alcance | Identificar con alias lógico el entorno, objetivo y origen/destino sin secretos | CM-DB-001 |
| Conectividad | Distinguir TCP, autenticación, consulta mínima y permisos efectivos | CM-DB-002 |
| Esquema | Comparar tablas, columnas, tipos, índices y relaciones con código/migraciones | CM-DB-003 |
| Correspondencia de cohortes | Confirmar clave estable y relación entre kodigo_cohorts y cohorts, si procede | CM-DB-004 |
| Admisiones/finanzas | Acordar metas vs valores reales, precisión/moneda, nulos y duplicados | CM-DB-004 |
| Estados/fechas | Definir alias, zona horaria, estado guardado/efectivo e hitos | CM-DB-004 |
| Calidad e integridad | Agregados, duplicados, huérfanos, campos requeridos, UTF-8 y totales consistentes | CM-DB-004 |

## Registro de verificaciones

| Fecha | Entorno lógico | Operación | Resultado | Evidencia | Límite |
| --- | --- | --- | --- | --- | --- |
| 2026-09-26 | Checkout local | Inspección de configuración, repositorios, inventario de migraciones y estructura de scripts | Parcial | [E-003](registro-cambios.md#e-003) | No prueba conexión, aplicación de DDL ni calidad de datos remotos |
| 2026-09-26 | Checkout local | Actualización de tablas consumidoras, migraciones 018–019 y diferencias locales | Parcial | [E-028](registro-cambios.md#e-028) | Entorno y objetivo operativo pendientes; sin conexión ni ejecución SQL |
| 2026-09-26 | SQLite en memoria y MariaDB local desechable | Fixtures sintéticas y consultas de dashboard para CM-PERF-001; bases temporales eliminadas al finalizar | Verificado | [E-029](registro-cambios.md#e-029) | No usa .env ni verifica acceso/esquema del entorno operativo de CM-DB-002/003 |

Añadir cada operación real con fecha, alias de entorno, ticket, consulta/procedimiento sanitizado, resultado y alcance. Para una transferencia, añadir el mapeo confirmado y el resumen de leídos/válidos/rechazados/duplicados/insertados/actualizados. Esos valores están pendientes, no son cero.

Registrar ensayos, errores, recuperación y conciliación por separado de aplicación. No pasar a aplicación sobre un entorno compartido por el mero hecho de completar el mapeo o una prueba local.
