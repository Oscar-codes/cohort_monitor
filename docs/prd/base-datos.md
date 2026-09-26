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
- [Migraciones](../../database/migrations/): secuencia observada 002–017; no se identificó aquí un registro de aplicaciones por entorno. Numeración o existencia no prueban ejecución.

| Grupo de archivos | Propósito visible | Límite |
| --- | --- | --- |
| 002–005 | Refactor de cohortes, autenticación/marketing, admisiones B2B y área | No certificados sobre un entorno real |
| 006 y 009 | Seeds históricos de cohortes | Incluyen sustitución/borrado de datos; no usarlos como actualización rutinaria |
| 007–008 | Índices de filtros y corrección de encoding | Falta contraste con esquema/datos actuales |
| 010–013 y 017 | Roles/usuarios y alineación de auditoría | No reproducir ni documentar valores de credenciales de seeds |
| 014–016 | Workflow/comentarios, estados de marketing e información de campañas | Comprobar compatibilidad con servicios actuales |

Hay varios dumps (`schema.sql`, `dump.sql`, `railway_dump.sql`, `railway_dump_live.sql`). Ninguno se declara copia actual de una base remota por su nombre. No ejecutarlos ni copiar sus filas a esta documentación.

Los scripts `_check_kodigo.php`, `_introspect.php`, `_kodigo_introspect.php` y `_schema.php` estaban sin seguimiento de Git. Solo se extrajeron tipos de consulta y referencias de estructura; no se publicaron sus literales de conexión ni se ejecutaron. Los archivos de claves y salidas SSH no se leyeron. No convertir esos artefactos en dependencia del tracker.

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

Añadir cada operación real con fecha, alias de entorno, ticket, consulta/procedimiento sanitizado, resultado y alcance. Para una transferencia, añadir el mapeo confirmado y el resumen de leídos/válidos/rechazados/duplicados/insertados/actualizados. Esos valores están pendientes, no son cero.

Registrar ensayos, errores, recuperación y conciliación por separado de aplicación. No pasar a aplicación sobre un entorno compartido por el mero hecho de completar el mapeo o una prueba local.
