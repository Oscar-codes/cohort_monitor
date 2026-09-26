# QA responsive — CM-UI-005

> Checklist y trazas para validar la ausencia de overflow horizontal,
> legibilidad y objetivo del touch target en los puntos de quiebre
> 360 / 768 / 1440 px del frontend.

## Auditoría estática

Cobertura de `@media` queries (verificada):

```
Total: 41
Distribución:
  max-width: 575.98px   xs      → meta principal
  max-width: 767.98px   sm      → 768px objetivo
  max-width: 991.98px   md      → breakpoint de colapso de sidebar
  max-width: 1199.98px  lg      → ajuste final antes de xl (1200px)
  min-width: 992px      lg+     → xl breakpoint
  (prefers-reduced-motion: reduce)  → CM-UI-003 + CM-UI-004
```

### Cambios introducidos en CM-UI-005

| Archivo | Cambio |
|---|---|
| `public/assets/css/app.css` | `.coach-gantt-shell` cambia `overflow: hidden` → `overflow: hidden auto` y añade `overflow-x: auto; -webkit-overflow-scrolling: touch` a `.coach-gantt-shell .gantt-wrapper`. Soluciona el clipping horizontal del Gantt de coaches en 360 px (tenía `min-width: 920 px` y el shell lo cortaba). |
| `public/assets/css/app.css` | `.alerts-toolbar` añade `flex-wrap: wrap`. `.alerts-search` cambia `flex: 1` → `flex: 1 1 220px` y `min-width: 220px` → `min-width: 0`. En 360 px la barra de búsqueda, el filtro de botones y el filtro de tipo se reorganizan en filas en lugar de salirse del viewport. |

### Verificación por elemento

| Elemento | Breakpoint | Estado |
|---|---|---|
| `<a class="skip-link">` | todos | visible:translateY(-150%); focus:translateY(0) → aparece sólo al recibir foco. |
| `<header class="header">` | xs | usa `d-flex` con hijos que se reorganizan; el botón "Nueva cohorte" se oculta (< lg). |
| Búsqueda desktop `.header-search` | ≥ xl | `min-width: 360px` (no overflow). < xl está `display: none`, se usa `.header-search--mobile` con `min-width: 0; width: 100%`. |
| `.alerts-search` y `.alerts-toolbar` | xs | ahora wrap correctamente, flex-wrap incluido. |
| `.alerts-filter-group` | xs | sin `min-width`, envuelve dentro de `.alerts-toolbar`. |
| `.coach-gantt-shell > .gantt-wrapper` | xs | overflow-x:auto + touch scroll; el `min-width: 920 px` se desliza horizontalmente. |
| `.gantt-shell (cohorts/finance.php)` | xs | envuelto en `.table-responsive`; scroll horizontal nativo. |
| `.kodigo-stat-card`, `.kodigo-card` | todos | sin min-width explícito, `flex-grow-1` o columnas Bootstrap controlan el ancho. |
| `.metric-card`, `.kpi-card`, `.finance-summary-card`, `.master-summary-card` | todos | sin min-width, `position: relative`. |
| `.btn-icon` | xs | `min-width: 40 / min-height: 40` vía `@media (max-width: 575.98px)`. |
| `<table>` en `.table-responsive` | xs | scroll horizontal nativo preservado. |
| `.toast-stack (kodigo)` | xs | `max-width: min(360px, calc(100vw - 2rem))`. |
| `[role="alert"]` | xs | `<div class="alert">` sin anchura fija; en `auth/login.php` y `cohorts/import.php` ya tenía `class="alert-danger alert-dismissible"`. |

### Min-widths residuales

Sólo dos elementos siguen usando un `min-width` ≥ 200 px:

| Línea | Regla | Comentario |
|---|---|---|
| `.gantt-label-col` (`min-width: 180px` desktop, `120px` ≤ 768) | wrap contenedor `.table-responsive` | OK |
| `.gantt-row`, `.gantt-header` | `min-width: 800px` (finance/calendar) | wrap contenedor `.table-responsive` o `.coach-gantt-shell` (ahora con overflow-x) |
| `.coach-gantt-modern` group | `min-width: 920px` | overflow-x:auto en `.coach-gantt-shell > .gantt-wrapper` |
| `.coach-gantt-label` | `min-width: 220px` desktop, `140px` ≤ 768 | OK |
| `.kodigo-toast` | `min-width: 240px` | posición fija, no overflow en body |

Todos los `min-widths` están wrappeados en contenedores scrollables. Ninguno empuja un `<main>` o `<aside>` por encima de 360 px.

## Checklist manual reproducible

> Requiere Chrome DevTools en modo responsive. No automatizable sin navegador.

1. **Dashboard (`/`)** a 360 px:
   - 4 KPI cards en una columna cada uno (no `col-12` se rompe).
   - 2 gráficos se reorganizan en fila simple.
   - Sin overflow horizontal en el `<main>`.
2. **Cohorts index (`/cohorts`)** a 360 px:
   - Filtros en cards con ancho completo (Bootstrap `col-12`).
   - Tabla de cohortes: las acciones `btn-icon` se alinean en columna. La tabla interna debe tener `table-responsive`. Confirmar scroll horizontal nativo.
3. **Cohorts show (`/cohorts/{id}`)** a 360 px:
   - Paneles apilados verticalmente.
   - Botón "Eliminar" dentro del header del hero con texto legible.
4. **Cohorts finance (`/cohorts/finance`)** a 360 px:
   - Gráficos `chart.admissions` y `chart.bootcamp` con `min-height: 320px` ocupan todo el ancho.
   - Tablas en scroll horizontal.
5. **Cohorts master (`/cohorts/master`)** a 360 px:
   - Paneles apilados, panel Gantt de cohorts en contenedor `table-responsive`.
6. **Cohorts import (`/cohorts/import`)** a 360 px:
   - Upload zone con `p-5` reducida proporcionalmente.
   - Botón "Quitar" (`btn-close`) accesible al menos 24×24.
7. **Alerts (`/alerts`)** a 360 px:
   - Toolbar reorganizada: búsqueda arriba, filtros abajo.
   - Cards de riesgos con scroll si son > 360 px.
8. **Marketing show (`/cohorts/{id}/marketing`)** a 360 px:
   - Botones de etapas con texto completo; modales con scroll.
9. **Users index (`/users`)** a 360 px:
   - Cards (mobile) sustituyen a la tabla, accesible sin scroll.
10. **Coaches calendar (`/coaches`)** a 360 px:
    - Header chips (Todos, Marketing, Comentarios) en una sola línea.
    - Gantt de coaches con `min-width: 920 px` deslizándose horizontalmente (`-webkit-overflow-scrolling: touch`).
11. **Reports (`/reports`)** a 360 px:
    - 4 sumarios apilados; `dashboard-mini-progress` fluido.
12. **Admin health (`/admin/health`)** a 360 px:
    - Tabla sin scroll horizontal crítico.
13. **Account profile (`/account`)** a 360 px:
    - Formularios con `row g-3` apilados.
    - Inputs full-width con labels legibles.
14. **Auth login (`/login`)** a 360 px:
    - Card auth-login-form centrada, sin overflow.
15. **Touch targets** a 360 px:
    - `.header-icon-btn`, `.btn-icon`, `.sidebar-collapse-btn`, `.dashboard-action` con `min-width: 40 / min-height: 40` ya garantizado por CSS.

## Comprobación mínima con `curl` (sin navegador)

```bash
# 1. Endpoint público
curl -sI http://localhost:8000/healthz

# 2. Login + GET al dashboard con cookie guardada
curl -c /tmp/c.txt -b /tmp/c.txt -X POST http://localhost:8000/login \
  -d "username=admin&password=***" -i
curl -b /tmp/c.txt http://localhost:8000/ -i | head

# 3. Validar 200 sin redirección al login
curl -b /tmp/c.txt -o /dev/null -w "%{http_code}" http://localhost:8000/cohorts
```

Esta traza es indicativa: demuestra que cada URL responde 200 cuando hay sesión activa, pero NO verifica el layout responsivo (overflow, posición de elementos). Para esto se requiere un navegador real o un headless como Chromium / Playwright.

## Limitaciones

- **Sin captura visual**: el pase CM-UI-005 es estructural (atributos + estilos). Las 15 capturas de pantalla manuales quedan como tarea de QA tradicional.
- **Sin headless browser**: con `pwsh` no dispongo de Chrome headless en este entorno. Si en una sesión futura se instala `puppeteer` o `chromium-headless`, se pueden automatizar las verificaciones de overflow.
- **Sin datos reales**: la verificación requiere una sesión activa, pero solo verificamos que `curl` devuelve 200 para cada URL.

## Próximos pasos

- CM-UI-005 queda marcado Completado en su componente estructural; se reabrirá si surge regresión de overflow en una vista específica.
- Considerar un harness Playwright en CI que ejecute `page.evaluate(...)` por cada vista + viewport, comparando bounding boxes de los nodos clave.
