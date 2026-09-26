# Rendimiento de listados y dashboard

Fecha: 2026-09-26. Ticket: [CM-PERF-001](prd/backlog.md). Evidencia: [E-029](prd/registro-cambios.md#e-029).

## Dashboard: consultas acotadas

Antes, `DashboardService` cargaba todas las cohortes, comentarios de riesgo y etapas en riesgo, para devolver cinco filas de cada grupo. Ahora esas tres consultas llevan `LIMIT 5`. Dos consultas `COUNT(*)` conservan el total completo de alertas, con los mismos joins y predicados de los listados. Los errores de base de datos siguen propagándose al controlador.

`CohortRepository::findFirst()` conserva el orden previo por fecha de inicio, nulos al final e ID ascendente. No cambia el significado de «recientes» a fecha de creación. Las alertas mantienen fecha descendente y añaden ID descendente como desempate estable; antes el orden entre fechas iguales no estaba definido.

Los métodos de alertas conservan la opción sin límite para sus consumidores existentes. Los límites explícitos se acotan entre 1 y 100. El listado completo de alertas no se trunca por este cambio.

## Medición reproducible

Ejecutar con PHP 8.2 o superior (el medidor utiliza `memory_reset_peak_usage`):

```powershell
php tests/dashboard_queries.php
# Solo contra un servidor local desechable con root sin contraseña:
php tests/dashboard_queries.php --mysql-port=13317
```

El harness no carga `.env`. Por defecto usa SQLite en memoria. En modo MySQL crea bases sintéticas con nombres aleatorios `cm_perf_test_*` y elimina únicamente esas bases al finalizar. No usa el esquema de la aplicación ni ejecuta migraciones/seeds del proyecto.

Se ejecutó con PHP 8.2.12, SQLite y MariaDB 10.4.32 en una instancia temporal separada, ligada a loopback. Las tablas de prueba contienen 0, 3 y 10 000 filas por grupo, además de casos que no deben entrar en las alertas. El esquema sintético no representa una copia del esquema desplegado.

Última ejecución, bloque de cohortes/comentarios/etapas y total de alertas:

| Motor / filas por grupo | Consultas antes → después | Pico adicional PHP antes → después (bytes) | Tiempo antes → después (ms) |
| --- | --- | --- | --- |
| SQLite / 0 | 3 → 5 | 3 848 → 3 736 | 0,110 → 0,113 |
| SQLite / 3 | 3 → 5 | 17 136 → 16 176 | 0,136 → 0,134 |
| SQLite / 10 000 | 3 → 5 | 41 143 832 → 24 296 | 99,012 → 5,817 |
| MariaDB / 0 | 3 → 5 | 46 224 → 45 544 | 15,700 → 8,757 |
| MariaDB / 3 | 3 → 5 | 58 192 → 56 600 | 9,385 → 9,554 |
| MariaDB / 10 000 | 3 → 5 | 42 331 712 → 64 760 | 212,770 → 104,029 |

El dashboard completo pasa de seis a ocho consultas; el harness verifica ocho en MariaDB. Las tres consultas modificadas devuelven como máximo 15 filas de detalle, más dos resultados escalares de conteo. Se reduce la transferencia y materialización en PHP, no necesariamente las filas examinadas por el motor: los conteos y ordenaciones aún deben procesar candidatos.

La línea base reproduce las cargas completas y el recorte en PHP, usando el desempate estable de las consultas actuales para poder comparar arrays exactos. Se verifican listas idénticas, total de 20 000 alertas para 10 000 por grupo, entradas vacías, límites negativos/cero/excesivos, fechas empatadas, inicios nulos, comentarios huérfanos y etapas sin autor. Una tabla ausente debe producir excepción, no un resultado vacío exitoso.

La medición de memoria excluye la creación de fixtures y corresponde al pico adicional de PHP durante cada bloque; no incluye memoria del servidor. Los tiempos son observaciones de una ejecución, no un benchmark estadístico ni una promesa de latencia en producción. SQLite omite la consulta existente de próximos inicios por su sintaxis MySQL; MariaDB ejecuta el servicio completo con prepares nativos.

## Trabajo restante para cerrar CM-PERF-001

- `CohortController::index()` sigue cargando todos los resultados filtrados: la vista calcula contadores por estado y un Gantt a 60 días sobre el mismo conjunto. Separar los totales y el Gantt antes de paginar las filas; conservar filtros, estados calculados por fechas y navegación.
- Plan Maestro y Finanzas también usan colecciones completas para sus agregados. Una paginación debe mantener totales globales, no recalcularlos sobre la página visible.
- El listado de alertas y usuarios conserva su contrato completo. Evaluar paginación por volumen real; exportaciones y reportes requieren una estrategia de lotes que conserve el conjunto completo.
- Medir las consultas e índices con `EXPLAIN` y volúmenes representativos en el entorno identificado por CM-DB-001/002. No se agregaron índices especulativos ni se validó rendimiento de producción.

CM-PERF-001 permanece **En progreso**: el bloque de dashboard está implementado y probado; la paginación de listados y la medición del entorno real siguen pendientes.
