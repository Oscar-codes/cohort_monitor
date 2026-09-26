# Plan de actualizacion UX/UI — Estilo Kodigo Academy
## Cohort Monitor

Plan enfocado en refrescar la capa visual y de animaciones de Cohort Monitor adoptando el lenguaje de diseno y motion del proyecto Kodigo Self Learning (referenciado por la habilidad `frontend-developer-kodigo-SKILL`) y los estandares de motion de Emil Kowalski (`review-animations`) y UX/WCAG (`ui-ux-pro-max`).

> Regla general: **no se modifican rutas, logica de negocio, campos, consultas, ni endpoints**. Solo se sustituyen/agregan clases CSS, atributos `data-*`, micro-interacciones JS declarativas y assets de motion. Cualquier cambio de dato exige ticket aparte.

---

## 1. Estado actual verificado

Inspeccion realizada sobre `main` (commit `2c905c3`).

### 1.1 Inventario de assets

| Archivo | Tamano | Observacion |
|---|---|---|
| `public/assets/css/app.css` | ~110 KB / 5.300 lineas | Define tokens `--app-*` en `:root`, bloques `.app-panel`, `.cohort-summary-card`, sidebar, header, modo compacto. |
| `public/assets/css/bootstrap.css` | 195 KB | Bootstrap 5 custom (no minificado en dev). |
| `public/assets/js/app.js` | ~11 KB | Inicializa sidebar, density, tooltips, validacion, `confirm()` nativo. |
| `public/assets/js/density-init.js` | 353 B | Aplica `html.app-density-compact` al cargar. |
| `public/assets/vendor/apexcharts/` | presente | Solo en `cohorts/finance`. |
| `public/assets/vendor/sweetalert2/` | presente | Confirmaciones modernas. |

### 1.2 Tokens existentes (resumen)

```css
:root {
  --app-bg: #f3f6fb;
  --app-surface: #ffffff;
  --app-surface-muted: #f8fafc;
  --app-border: #e2e8f0;
  --app-text: #0f172a;
  --app-muted: #64748b;
  --app-primary: #2563eb;
  --app-success: #16a34a;
  --app-info: #0891b2;
  --app-warning: #f59e0b;
  --app-danger: #dc2626;
  --app-radius: 14px;
  --app-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.06);
  --app-shadow-md: 0 14px 34px rgba(15, 23, 42, 0.09);
}
```

### 1.3 Animaciones y motion actuales

Auditadas con busqueda de `transition|animation|@keyframes` en `app.css`. Problemas detectados contra los 10 estandares no negociables de `review-animations/SKILL.md`:

| Hallazgo | Archivo (aprox.) | Estandar violado |
|---|---|---|
| `transition: all 0.15s ease` (varios) | sidebar, links | 1 + 7 (animar `all` dispara layout) |
| `transition: all 0.2s ease` (cards, dropdowns) | `.app-panel`, `.dropdown-menu` | 1 + 7 |
| `transition: width 0.45s ease` en sidebar | sidebar | 4 (>300ms) + 7 (layout prop) |
| Ausencia de cubic-bezier custom | global | 3 (easing debil built-in) |
| Sin `transform-origin` en dropdowns/popovers | header dropdown | 5 |
| Sin `@media (hover: hover) and (pointer: fine)` en hovers | global | 8 |
| `prefers-reduced-motion` solo reduce a `0.01ms` | bloque global al final | 8 (mejor mantener opacidad/color) |
| Sin stagger en entradas | cards KPI | 10 (cohesion) |

### 1.4 Inventario de pantallas a actualizar (sin cambiar logica)

1. `app/Views/layouts/main.php` — shell.
2. `app/Views/partials/sidebar.php` y `header.php` — chrome.
3. `app/Views/dashboard/index.php` — `/`.
4. `app/Views/cohorts/index.php` — `/cohorts`.
5. `app/Views/cohorts/master.php` — `/cohorts/master`.
6. `app/Views/cohorts/finance.php` — `/cohorts/finance` (incluye graficos ApexCharts).
7. `app/Views/cohorts/show.php` — detalle.
8. `app/Views/cohorts/create.php` / `edit.php` — formularios.
9. `app/Views/cohorts/import.php` — importacion.
10. `app/Views/marketing/index.php` y `show.php`.
11. `app/Views/alerts/index.php`.
12. `app/Views/reports/index.php`.
13. `app/Views/users/index.php` / `create.php` / `edit.php`.
14. `app/Views/coaches/calendar.php`.
15. `app/Views/account/profile.php`.
16. `app/Views/auth/login.php`.
17. `app/Views/admin/audit-log.php` y `health.php`.

---

## 2. Principios Kodigo que se adoptan

Extraidos de `.agents/skills/frontend-developer-kodigo-SKILL/SKILL.md` y aplicados al contexto PHP server-rendered:

1. **Composicion sobre abstracciones genericas.** Reutilizar clases existentes (`.app-panel`, `.cohort-summary-card`, `.cohorts-hero`); anadir modificadores con guiones, no nuevos sistemas.
2. **Estados primero.** Hover/focus/active/disabled/loading/empty/error deben existir antes de decorar.
3. **Tokens antes que valores.** Cualquier color/duracion/sombra se lee de variables CSS.
4. **Tokens semanticos.** `--app-success/-warning/-danger/-info/-neutral` ya existen; se anade `--app-primary-soft`, `--app-primary-strong`, `--app-focus-ring`.
5. **Responsabilidad del servidor.** Validacion/autorizacion no cambian; el cliente solo refleja.
6. **Cohesion con el dominio.** Producto operacional (cohortes, finanzas), por tanto motion sobrio, datos legibles sobre decoracion.

### 2.1 Personalidad del motion

Cohort Monitor es **operacional**: la gente entra 20+ veces al dia. Segun `review-animations/SKILL.md`:

| Frecuencia de uso | Decision |
|---|---|
| Hover de filas, links, iconos del sidebar | Sin animacion o micro (<120ms) |
| Cambio de pestana / filtro | Transicion 150-180ms |
| Dropdown de usuario, density toggle | 180-220ms con easing custom |
| Toast / confirmacion | 200-280ms entrada, 150ms salida |
| Aparicion inicial del dashboard | Stagger 30-50ms entre cards (max 6 items) |
| Graficos al cambiar filtro | Re-render Apex, sin entrada animada |

---

## 3. Sistema de tokens Kodigo (nuevo)

Se anade a `:root` en `public/assets/css/app.css` sin romper tokens previos.

### 3.1 Color y superficie

```css
:root {
  /* Kodigo brand tokens */
  --kodigo-blue-50:  #eff5ff;
  --kodigo-blue-100: #dbe7ff;
  --kodigo-blue-300: #7aa2f7;
  --kodigo-blue-500: #2563eb;
  --kodigo-blue-600: #1d4ed8;
  --kodigo-blue-700: #1e40af;

  --kodigo-violet-500: #7c3aed;
  --kodigo-cyan-500:   #06b6d4;

  /* Semantic aliases (mantener compatibilidad) */
  --app-primary:        var(--kodigo-blue-500);
  --app-primary-soft:   var(--kodigo-blue-50);
  --app-primary-strong: var(--kodigo-blue-700);

  /* Surface elevation scale */
  --app-elev-0: 0 0 0 transparent;
  --app-elev-1: 0 1px 2px rgba(15, 23, 42, .06), 0 1px 1px rgba(15, 23, 42, .04);
  --app-elev-2: 0 4px 14px rgba(15, 23, 42, .08), 0 1px 2px rgba(15, 23, 42, .04);
  --app-elev-3: 0 14px 34px rgba(15, 23, 42, .10), 0 4px 10px rgba(15, 23, 42, .06);
}
```

### 3.2 Motion (curvas y duraciones)

Adoptar curvas de Emil Kowalski (`review-animations/STANDARDS.md`):

```css
:root {
  --ease-out:    cubic-bezier(0.23, 1, 0.32, 1);
  --ease-in-out: cubic-bezier(0.77, 0, 0.175, 1);
  --ease-drawer: cubic-bezier(0.32, 0.72, 0, 1);

  --dur-press:    120ms;   /* :active feedback */
  --dur-hover:    150ms;   /* color/border hover */
  --dur-pop:      180ms;   /* dropdown, tooltip */
  --dur-overlay:  220ms;   /* modal, drawer, sheet */
  --dur-page:     260ms;   /* route-level reveal */
}
```

Reglas:
- **Nunca** `transition: all`. Especificar propiedades (`transform`, `opacity`, `background-color`, `border-color`, `box-shadow`, `color`).
- **Nunca** `ease-in` en UI. Solo `--ease-out`, `--ease-in-out`, `--ease-drawer`, `linear` (solo spinners/progress).
- **Nunca** `scale(0)` en entradas. Usar `scale(0.96)` + `opacity: 0`.
- **Sub-300ms** en cualquier UI; si supera, justificar.
- **GPU-only**: animar `transform` y `opacity`. Nunca `width/height/top/left/margin/padding` salvo barra de progreso.

### 3.3 Radios, sombras, tipografia

```css
:root {
  --app-radius-sm: 8px;
  --app-radius:    12px;     /* antes 14, ajustamos para look Kodigo */
  --app-radius-lg: 20px;
  --app-radius-pill: 999px;

  --app-focus-ring: 0 0 0 3px rgba(37, 99, 235, .35);
  --app-focus-ring-danger: 0 0 0 3px rgba(220, 38, 38, .30);

  --app-shadow-sm: var(--app-elev-1);
  --app-shadow-md: var(--app-elev-2);
  --app-shadow-lg: var(--app-elev-3);

  /* Typography scale (rem) */
  --fs-display: 2.25rem;
  --fs-h1: 1.625rem;
  --fs-h2: 1.25rem;
  --fs-h3: 1.0625rem;
  --fs-body: .9375rem;
  --fs-caption: .8125rem;

  --lh-tight: 1.2;
  --lh-snug: 1.35;
  --lh-base: 1.5;
}
```

### 3.4 Z-index

```css
:root {
  --z-base:    0;
  --z-sticky:  20;
  --z-overlay: 40;
  --z-dropdown: 50;
  --z-modal:   1000;
  --z-toast:   1080;
}
```

---

## 4. Componentes Kodigo a introducir

Todos modificadores (sin nuevos sistemas). La primera linea de cada uno es el hook HTML.

### 4.1 `app-card` (panel unificado)

Reemplaza `.app-panel` por `.kodigo-card` cuando se necesite look Kodigo; `.app-panel` se conserva para retro-compatibilidad.

```html
<section class="kodigo-card" data-elevation="2">
  <header class="kodigo-card__header">
    <h3 class="kodigo-card__title">Titulo</h3>
    <div class="kodigo-card__actions">…</div>
  </header>
  <div class="kodigo-card__body">…</div>
</section>
```

```css
.kodigo-card {
  background: var(--app-surface);
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius);
  box-shadow: var(--app-elev-1);
  transition: box-shadow var(--dur-hover) var(--ease-out),
              transform  var(--dur-hover) var(--ease-out);
}
.kodigo-card[data-elevation="2"] { box-shadow: var(--app-elev-2); }
.kodigo-card[data-interactive="true"]:hover {
  transform: translateY(-2px);
  box-shadow: var(--app-elev-3);
}
```

### 4.2 `kodigo-button`

Reemplazar las clases `btn-primary` cuando se requiera look Kodigo sin romper Bootstrap. Solo aporta estilos visuales; no toca logica.

```css
.btn-kodigo {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .5rem 1rem;
  border-radius: var(--app-radius-sm);
  font-weight: 600;
  background: var(--app-primary);
  color: #fff;
  border: 1px solid var(--app-primary);
  transition: background-color var(--dur-hover) var(--ease-out),
              border-color   var(--dur-hover) var(--ease-out),
              transform      var(--dur-press) var(--ease-out),
              box-shadow     var(--dur-hover) var(--ease-out);
}
.btn-kodigo:hover { background: var(--app-primary-strong); }
.btn-kodigo:active { transform: scale(0.97); }
.btn-kodigo:focus-visible { outline: none; box-shadow: var(--app-focus-ring); }
```

### 4.3 `kodigo-stat-card` (KPI)

```html
<article class="kodigo-stat-card" data-trend="up|down|flat">
  <span class="kodigo-stat-card__icon"><i class="bi bi-…"></i></span>
  <div class="kodigo-stat-card__body">
    <strong class="kodigo-stat-card__value">…</strong>
    <small class="kodigo-stat-card__label">…</small>
    <span class="kodigo-stat-card__delta">+12%</span>
  </div>
</article>
```

```css
.kodigo-stat-card {
  display: flex;
  gap: .875rem;
  padding: 1rem 1.125rem;
  border-radius: var(--app-radius);
  background: var(--app-surface);
  border: 1px solid var(--app-border);
  box-shadow: var(--app-elev-1);
  transition: transform var(--dur-hover) var(--ease-out),
              box-shadow var(--dur-hover) var(--ease-out);
}
.kodigo-stat-card:hover { transform: translateY(-1px); box-shadow: var(--app-elev-2); }
.kodigo-stat-card__icon { /* icono 36x36 con fondo tonal */ }
.kodigo-stat-card__delta[data-direction="up"]   { color: var(--app-success); }
.kodigo-stat-card__delta[data-direction="down"] { color: var(--app-danger); }
```

### 4.4 `kodigo-status-pill`

Sustituye badges actuales en cohortes/marketing/alertas/usuarios.

```html
<span class="kodigo-pill" data-tone="success|warning|danger|info|neutral">
  <span class="kodigo-pill__dot" aria-hidden="true"></span>
  Texto
</span>
```

```css
.kodigo-pill {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .25rem .625rem;
  border-radius: var(--app-radius-pill);
  font-size: var(--fs-caption);
  font-weight: 600;
  background: var(--app-surface-muted);
  color: var(--app-text);
}
.kodigo-pill[data-tone="success"] { background: rgba(22, 163, 74, .10); color: #15803d; }
.kodigo-pill[data-tone="warning"] { background: rgba(245, 158, 11, .14); color: #b45309; }
.kodigo-pill[data-tone="danger"]  { background: rgba(220, 38, 38, .10); color: #b91c1c; }
.kodigo-pill[data-tone="info"]    { background: rgba(8, 145, 178, .10); color: #0e7490; }
```

### 4.5 `kodigo-empty-state`

```html
<div class="kodigo-empty" role="status">
  <i class="bi bi-inbox kodigo-empty__icon" aria-hidden="true"></i>
  <h4>Sin resultados</h4>
  <p>Ajusta los filtros o importa datos.</p>
  <button class="btn-kodigo">Accion principal</button>
</div>
```

### 4.6 `kodigo-toast`

Toasts coherentes con SweetAlert2 (ya presente). Helper JS en `app.js`:

```js
window.kodigoToast = ({ tone = 'info', title, message, timeout = 3500 } = {}) => {
  const node = Object.assign(document.createElement('div'), {
    className: `kodigo-toast kodigo-toast--${tone}`,
    role: 'status',
  });
  node.innerHTML = `<strong>${title}</strong><p>${message ?? ''}</p>`;
  document.querySelector('.kodigo-toast-stack')?.appendChild(node);
  setTimeout(() => node.remove(), timeout);
};
```

```css
.kodigo-toast {
  background: var(--app-surface);
  border: 1px solid var(--app-border);
  border-left: 3px solid var(--app-primary);
  border-radius: var(--app-radius);
  box-shadow: var(--app-elev-2);
  padding: .75rem 1rem;
  min-width: 240px;
  transform: translateY(8px);
  opacity: 0;
  transition: transform var(--dur-overlay) var(--ease-out),
              opacity var(--dur-overlay) var(--ease-out);
}
.kodigo-toast--mounted { transform: translateY(0); opacity: 1; }
```

### 4.7 Stagger reveal (entrada del dashboard)

Usar CSS `@starting-style` cuando este disponible (Chrome 117+, Safari 17.5+). Fallback con clase `.is-revealed`.

```css
.kodigo-reveal {
  opacity: 1;
  transform: translateY(0);
  transition: opacity var(--dur-page) var(--ease-out),
              transform var(--dur-page) var(--ease-out);
}
.kodigo-reveal:not(.is-revealed) {
  opacity: 0;
  transform: translateY(8px);
}
.kodigo-reveal[data-stagger="1"] { transition-delay: 40ms; }
.kodigo-reveal[data-stagger="2"] { transition-delay: 80ms; }
.kodigo-reveal[data-stagger="3"] { transition-delay: 120ms; }
.kodigo-reveal[data-stagger="4"] { transition-delay: 160ms; }
.kodigo-reveal[data-stagger="5"] { transition-delay: 200ms; }
.kodigo-reveal[data-stagger="6"] { transition-delay: 240ms; }
```

JS minimo en `app.js`:

```js
document.querySelectorAll('.kodigo-reveal').forEach((el, i) => {
  el.style.transitionDelay = `${Math.min(i, 6) * 40}ms`;
  requestAnimationFrame(() => el.classList.add('is-revealed'));
});
```

---

## 5. Patron de motion aplicado a componentes existentes

| Componente | Antes | Despues |
|---|---|---|
| Sidebar collapse | `transition: width 0.45s ease` | `transition: width var(--dur-page) var(--ease-drawer), transform var(--dur-page) var(--ease-out)`; backdrop mobile con `opacity` 220ms. |
| Sidebar link hover | `transition: all 0.15s ease` | `transition: background-color var(--dur-hover) var(--ease-out), color var(--dur-hover) var(--ease-out); @media (hover: hover) and (pointer: fine)`. |
| Dropdown usuario | `transition: all 0.2s ease` | `transition: transform var(--dur-pop) var(--ease-out), opacity var(--dur-pop) var(--ease-out); transform-origin: top right; @starting-style { opacity: 0; transform: scale(0.96) translateY(-4px); }`. |
| Density toggle | sin animacion | `transition: background-color var(--dur-hover), color var(--dur-hover), transform var(--dur-press)`. |
| Cards (`cohort-summary-card`, `kodigo-stat-card`) | `transition: all 0.2s ease` | `transition: transform var(--dur-hover) var(--ease-out), box-shadow var(--dur-hover) var(--ease-out)` + `transform: translateY(-1px)` solo en `@media (hover: hover)`. |
| Botones primarios | sin transicion coherente | `.btn-kodigo` (ver 4.2). |
| Filas de tabla | `transition: all 0.15s ease` | `transition: background-color var(--dur-hover) var(--ease-out)`. Sin `transform` (es layout prop en filas). |
| Toasts/flash | Bootstrap alerts, sin transicion | Migrar a `.kodigo-toast` (ver 4.6) con `@starting-style`. |
| ApexCharts | Re-render directo | Mantener re-render; anadir `chart: { animations: { enabled: true, speed: 220, easing: 'easeout' } }`. |
| Sidebar mobile offcanvas | ya usa Bootstrap | Asegurar `data-bs-transition="200"` equivalente via `--bs-offcanvas-transition-duration`. |

---

## 6. Auditoria previa (lo que NO se hace)

- No se sustituye Bootstrap por Tailwind u otro framework.
- No se introduce React, Vue ni HTMX.
- No se modifican rutas, controladores, servicios, repositorios, ni migraciones.
- No se cambian campos visibles, etiquetas de columnas ni formato de exportacion.
- No se sube la version de Bootstrap ni se cambian los assets `vendor/` ya copiados.
- No se aniade CSS nuevo en CDN; todo queda local.
- No se aniaden motion en acciones de 100+ veces al dia (submit de formulario rapido, navegacion por teclado). Ver `review-animations/SKILL.md` tabla de frecuencia.

---

## 7. Fases

### Fase 0 — Baseline de motion (sin cambios visuales grandes)

**Objetivo:** introducir tokens y helpers, dejar la base lista.

| Tarea | Archivo |
|---|---|
| Anadir tokens Kodigo (3.1–3.4) | `public/assets/css/app.css` |
| Crear helper `kodigoToast` y mount del stack | `public/assets/js/app.js` |
| Sustituir `transition: all` por transiciones explicitas en componentes core (sidebar, header, dropdown) | `public/assets/css/app.css` |
| Crear bloque `prefers-reduced-motion: reduce` que conserve color/opacity y elimine transform | `public/assets/css/app.css` |
| Auditar consola y lint sin cambios de logica | `php -l` por archivo PHP modificado (no deberia haber) |

**Criterio de aceptacion:** pagina se ve igual, pero con micro-interacciones mejor calibradas; consola limpia; sin errores JS; `prefers-reduced-motion: reduce` validado en DevTools.

### Fase 1 — Sistema Kodigo (componentes)

**Objetivo:** introducir `.kodigo-*` y un paralelo a los paneles actuales.

| Tarea | Archivos |
|---|---|
| `.kodigo-card`, `.kodigo-card__header/body/actions` | `public/assets/css/app.css` |
| `.btn-kodigo` | `public/assets/css/app.css` |
| `.kodigo-stat-card` | `public/assets/css/app.css` |
| `.kodigo-pill` | `public/assets/css/app.css` |
| `.kodigo-empty` | `public/assets/css/app.css` |
| Stagger reveal con `@starting-style` + fallback | `public/assets/css/app.css`, `public/assets/js/app.js` |
| Reemplazar visualmente (no semanticamente) en `dashboard/index.php` | vista |
| Reemplazar visualmente en `cohorts/index.php` | vista |
| Reemplazar visualmente en `cohorts/finance.php` | vista |

Mantener atributos Bootstrap (`class="row"`, `class="col-..."`) y solo aplicar `.kodigo-*` ademas; nada de reescribir HTML desde cero.

### Fase 2 — Estados y feedback

| Tarea | Archivos |
|---|---|
| Convertir alerts Bootstrap actuales a `.kodigo-toast` | `public/assets/js/app.js` (helper), vistas que imprimen `Auth::getFlash` |
| Reemplazar `confirm()` por SweetAlert2 con estilo Kodigo (botones, icono, color) | `public/assets/js/app.js`, `cohorts/index.js`, `cohorts-edit.js`, `users-form.js`, `cohorts-import.js` |
| Empty states visuales con `.kodigo-empty` | todas las vistas con tablas |
| Skeleton loading en cards dashboard (solo si la fuente es lenta) | `public/assets/css/app.css`, dashboard.js |

### Fase 3 — Dashboard ejecutivo Kodigo

| Tarea | Vista |
|---|---|
| KPI cards con `.kodigo-stat-card` y delta | `dashboard/index.php` |
| Mini sparkline (Chart.js ya usado o inline SVG) en cada stat | `dashboard.js` |
| Stagger reveal de las 6 primeras cards | `app.js` |
| Aplicar tipografia Kodigo (`--fs-h1`, etc.) | `app.css` + vista |

### Fase 4 — Tablas y listas

| Tarea | Vista |
|---|---|
| Sustituir badges de estado por `.kodigo-pill` | cohorts, alerts, marketing, users, reports |
| Hover de fila: `background-color` con transition 150ms | `app.css` |
| Cabecera sticky con fondo solido | `app.css` |
| Icono + texto en acciones, no solo icono | cohorts/index.php, alerts/index.php |

### Fase 5 — Formularios

| Tarea | Vista |
|---|---|
| Inputs con focus ring `var(--app-focus-ring)` | create/edit/import |
| Helpers y errores inline con icono | create/edit/import |
| Boton primario `.btn-kodigo` | create/edit/import |
| Toggle de password con icono en login | auth/login.php, account/profile.php |

### Fase 6 — Sidebar, header, chrome

| Tarea | Archivos |
|---|---|
| Categorias Principales / Operacion / Analitica / Admin en sidebar | `partials/sidebar.php` (no tocar permisos) |
| Active state con barra lateral Kodigo (3px) | `app.css` |
| Density toggle con icono y micro-animacion | `header.php`, `density-init.js`, `app.js` |
| Toaster stack global abajo a la derecha | `layouts/main.php`, `app.js` |

### Fase 7 — Modo compacto y accesibilidad

| Tarea | Archivos |
|---|---|
| Verificar que `app-density-compact` respete tokens nuevos | `app.css` |
| `prefers-reduced-motion` ya en Fase 0; anadir toggle manual opcional | `app.js`, `header.php` |
| Foco visible en todos los interactivos (auditar) | `app.css` |
| Roles ARIA consistentes en icon-buttons | partials |
| Contraste minimo 4.5:1 en tonos semanticos | `app.css` |

### Fase 8 — QA responsive final

| Vista | Breakpoint |
|---|---|
| Dashboard | 360 / 768 / 1024 / 1440 |
| Cohorts index | idem |
| Finance | 768 / 1440 (graficos) |
| Login | 360 / 768 |
| Sidebar offcanvas | 360 (mobile) |

Criterio: ninguna fila con overflow horizontal; CTAs >= 40px; graficos con fallback textual cuando no hay datos.

---

## 8. Definition of Done (por cambio visual)

- [ ] No cambia logica, campos, rutas ni permisos.
- [ ] No introduce libreria nueva global.
- [ ] Respeta `prefers-reduced-motion: reduce`.
- [ ] Especifica propiedades animadas (no `transition: all`).
- [ ] Duracion < 300ms en UI.
- [ ] Easing es uno de `--ease-out`, `--ease-in-out`, `--ease-drawer`, `linear` (spinners).
- [ ] Hover con `@media (hover: hover) and (pointer: fine)`.
- [ ] Foco visible (`var(--app-focus-ring)`).
- [ ] Contraste >= 4.5:1 (texto), 3:1 (large text).
- [ ] Estado vacio presente cuando aplica.
- [ ] Probado en 360, 768 y 1440.
- [ ] Sin overflow horizontal no intencionado.
- [ ] No bloquea la pagina si un elemento no existe (JS defensivo con `querySelector` y `?.`).
- [ ] Sin console errors ni warnings nuevos.
- [ ] `php -l` (cuando aplique) y `node -c` sobre JS modificado.

---

## 9. Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| Regresion visual en paginas no contempladas | Aplicar cambios primero a una pantalla, validar, expandir |
| `transition: all` heredado en componentes no listados | Auditoria por `grep -n 'transition: all' public/assets/css/app.css` antes de cerrar cada fase |
| Animacion molesta para usuarios power | Stagger max 6 items, duracion maxima 260ms, todo gated por reduced-motion |
| Graficos ApexCharts parpadean al re-render | Mantener `chart: { redrawPaths: true }` y `animations.speed <= 240` |
| Romper layout al cambiar radios | Migrar radios gradualmente: 14 -> 12 solo en nuevas clases `.kodigo-*` |
| Cache de assets con tokens viejos | Mover tokens a bloque unico al inicio de `app.css` y bumpear version via query string en `layouts/main.php` (`?v=2`) |

---

## 10. Orden de ejecucion recomendado

1. Fase 0 (tokens y motion baseline).
2. Fase 1 (componentes `.kodigo-*`).
3. Fase 4 (tablas) — impacto visible alto, bajo riesgo.
4. Fase 3 (dashboard) — impacto ejecutivo.
5. Fase 5 (formularios).
6. Fase 2 (feedback/toasts).
7. Fase 6 (sidebar/header).
8. Fase 7 (modo compacto y accesibilidad transversal).
9. Fase 8 (QA).

Cada fase termina con un commit pequeno y verificable; nada se combina con cambios de logica.

---

## 11. Verificacion

- `git diff --stat` por fase: solo `public/assets/css/app.css`, `public/assets/js/app.js`, archivos en `app/Views/` (sin tocar controladores/servicios/repositorios).
- `php -l` sobre cualquier vista modificada.
- `node -c public/assets/js/app.js` cuando se toque JS.
- Validar manualmente en navegador:
  - Con DevTools "Emulate CSS prefers-reduced-motion: reduce" activado.
  - Con throttling 4x CPU.
  - En 360, 768 y 1440.

---

## 12. Trazabilidad con el tracker

Este plan se vincula a tickets del tracker `docs/prd/backlog.md`:

- Ticket nuevo sugerido: **CM-UI-001** — Adoptar sistema visual Kodigo (Fases 0–2).
- Ticket nuevo sugerido: **CM-UI-002** — Refresh de dashboard con stat-cards y sparklines (Fase 3).
- Ticket nuevo sugerido: **CM-UI-003** — Estados, pills, empty states y toasts (Fases 2 y 4).
- Ticket nuevo sugerido: **CM-UI-004** — Sidebar/header con categoria Kodigo y focus ring (Fase 6).
- Ticket nuevo sugerido: **CM-UI-005** — Auditoria de accesibilidad y prefers-reduced-motion (Fase 7).

Cada ticket se cierra con evidencia en `docs/prd/registro-cambios.md` y referencia a la fase cumplida.
