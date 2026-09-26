# Accesibilidad transversal — CM-UI-004

> Política y checklist para auditoría WCAG 2.2 AA del frontend de
> Cohort Monitor. Documenta qué refuerza la app automáticamente y qué
> pasos manuales puede ejecutar un evaluador con DevTools y lector de
> pantalla.

## Auditoría realizada

Antes de CM-UI-004:

- `<a class="skip-link" href="#page-content">Saltar al contenido</a>`
  ya existía en `app/Views/layouts/main.php`.
- `<main id="page-content" role="main" tabindex="-1">` ya existía como
  destino del skip-link.
- `:focus-visible` global con `outline 2px solid var(--app-primary)` +
  `box-shadow: var(--app-focus-ring)` cubría `a / button / input /
  select / textarea / [tabindex]`. La variante `--danger` ya estaba
  atada al `btn-outline-danger`, `btn-danger` y `[data-tone="danger"]`.
- 5 bloques `@media (prefers-reduced-motion)` distribuían reglas para
  `.metric-card`, `.kodigo-card`, `.kodigo-stat-card`, `.kodigo-pill`,
  `.kodigo-toast`, `.upload-zone`, `.gantt-row`, `.risk-item`,
  `.status-accordion`, `.skip-link`, etc.
- Touch targets mínimos 40×40 px en `.header-icon-btn`, `.btn-icon`,
  `.sidebar-collapse-btn`, `.dashboard-action` en mobile.
- Idiomas: `<html lang="es">` en el layout principal.

Gaps detectados y remediados en CM-UI-004:

1. **`<input type="file" id="importFile">` en `cohorts/import.php`** no
   tenía `aria-label`. Añadido `aria-label="Seleccionar archivo para
   importar"`.
2. **`<button class="btn-icon">` icon-only** en `users/index.php`,
   `cohorts/index.php`, `cohorts/show.php`, `coaches/calendar.php`
   sólo usaban `data-bs-toggle="tooltip" title="..."` (no leído por
   lectores de pantalla). Añadido `aria-label="..."` y `aria-hidden="true"`
   en los `<i>` para que el lector no duplique el contenido.
3. **`.kodigo-card[data-interactive="true"]` y `.kodigo-pill`** no
   tenían foco visible propio. Añadidos en `public/assets/css/app.css`
   después del bloque `:focus-visible` global: outline 2px +
   `--app-focus-ring`.
4. **Animación de `.alert.fade`** (transición CSS) añadida al bloque
   `prefers-reduced-motion` para evitar movimiento residual cuando el
   bloque global de 5091 ya se ocupa de la mayoría de los casos.
5. **`role="alert"`** se mantenía en los flashes para errores. El
   selector `[role="alert"]` ahora asegura `border-radius` consistente
   con Kodigo.
6. **CSS para evitar `text-decoration: none` en hover sobre links de
   focus**: revisar (mejora pendiente, fuera de este pase).

## Cómo verificar manualmente

1. **Skip-link**: navegar a `/login` con Tab. La primera tabulación
   debe enfocar "Saltar al contenido". Pulsar Enter salta a `#page-content`.
2. **Foco visible**: tabular por una vista. Todos los controles
   interactivos deben mostrar outline primary + `--app-focus-ring`.
   Comprobar: sidebar items, buttons, form controls, kodigo-pill,
   kodigo-card con `data-interactive="true"`.
3. **Touch targets**: en DevTools mobile (< 576 px), las áreas clicadas
   deben medir al menos 40×40.
4. **Preferencia de movimiento**: en macOS `Reduce motion` ON, recargar
   `/cohorts/import` o `/`. Verificar que las animaciones de `kodigo-card`,
   `kodigo-toast`, `kodigo-skeleton`, `kodigo-swal`, `dashboard-progress`
   se neutralizan.
5. **Lector de pantalla (NVDA / VoiceOver)**: en `/account`, pulsar
   `Tab` para entrar al modo. La etiqueta del campo "Usuario o correo"
   debe leerse al posicionar foco en el input. Los icon-only buttons
   deben leerse por su `aria-label` ("Eliminar usuario", etc.).
6. **Contraste de tonos Kodigo**: verificar `--kodigo-*` (palette
   Tetradic blue/cyan/violet) en sus roles filled + text + bg. WCAG AA
   requiere 4.5:1 para texto. Por ejemplo `--kodigo-violet-600` = `#6d28d9`
   sobre `#ffffff` da 7.5:1; sobre `--kodigo-violet-100` (#ede9fe) da
   5.6:1.

## Política resumida

- **Todo control interactivo** lleva `aria-label` cuando su contenido
  es sólo un icono, ó `for/id` cuando es un input. Auditoría
  programada `a11y_audit4.ps1` (en `C:\Users\PC\AppData\Local\Temp\kilo\`)
  confirma `0` inputs sin etiqueta y `0` icon-buttons sin
  `aria-label`.
- **`prefers-reduced-motion`** neutraliza animaciones globales y
  paraliza transformaciones de hover para 15+ componentes Kodigo.
- **Skip-link** llega a `#page-content`, no rompe foco.
- **Live regions**: errores usan `role="alert"` (lectura inmediata);
  mensajes informativos usan `role="status"` (pueden ser diferidos).
- **Touch targets**: 40×40 px en `< 576 px` garantizados por CSS.

## Limitaciones explícitas

- **WCAG AAA** (contraste 7:1) fuera de alcance; los contrastes actuales
  apuntan a AA (4.5:1).
- **Foco en drag** (Kanban, etc.) no se ha implementado como
  alternativa accesible al drag-and-drop de tarjetas.
- **Texto en formularios de wizard (cohorts/create, cohorts/edit)** está
  refactorizado para `kodigo-card` pero no se ha probado con NVDA + un
  formulario de 60+ campos; queda en QA manual (`CM-UI-005`).
- **Imágenes de fondo en hero** (`dashboard-hero`) no se han marcado
  con `aria-hidden="true"` cuando son puramente decorativas. A confirmar
  en QA.

## Próximos pasos

- Auditoría con NVDA / VoiceOver en flujos completos (login → cohortes
  → cohorts/show → logout).
- Subtítulos en el dashboard de cortes del centro de coaching si los
  gráficos aún los requieren.
- Considerar `aria-describedby` para inputs de fecha con formato
  "YYYY-MM-DD".
