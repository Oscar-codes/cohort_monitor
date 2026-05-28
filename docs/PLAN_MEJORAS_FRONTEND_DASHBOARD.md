# Plan avanzado de mejoras Frontend Dashboard
## Cohort Monitor

Documento creado para guiar la modernizacion visual, UX y UI de Cohort Monitor usando como base los componentes disponibles en `Plantilla Dashboard/HTML`.

El objetivo no es reemplazar la aplicacion completa por la plantilla, sino adoptar de forma selectiva sus patrones mas utiles: cards avanzadas, widgets, graficas, tablas enriquecidas, estados visuales, microinteracciones, sidebar, formularios, calendario y feedback contextual.

---

## 1. Diagnostico actual

### Stack actual

| Area | Estado actual |
|------|---------------|
| Backend | PHP MVC custom |
| UI base | Bootstrap 5.3 por CDN |
| Iconos | Bootstrap Icons por CDN |
| CSS propio | `public/assets/css/app.css` |
| JS propio | `public/assets/js/app.js` |
| Layout | `app/Views/layouts/main.php` |
| Dashboard | `app/Views/dashboard/index.php` |
| Navegacion | `app/Views/partials/sidebar.php`, `app/Views/partials/header.php` |

### Fortalezas actuales

- Layout funcional con sidebar fijo en desktop y offcanvas en mobile.
- Dashboard con KPIs, acciones rapidas, progreso global, alertas recientes y cohortes recientes.
- Uso consistente de Bootstrap, badges, progress bars, tablas responsive y tooltips.
- JS modular para sidebar, validacion, tooltips y confirmaciones.
- Buen punto de partida para mejoras incrementales sin reescribir el backend.

### Problemas principales de UX/UI

| Problema | Impacto |
|----------|---------|
| Exceso de UI estatica | El dashboard se siente correcto pero poco dinamico. |
| KPIs sin tendencia visual | No queda claro si los indicadores suben, bajan o requieren atencion. |
| Graficas ausentes | La informacion operativa depende demasiado de barras simples y tablas. |
| Acciones rapidas planas | Son utiles, pero no priorizan flujos ni estado del usuario. |
| Header poco informativo | Falta busqueda, alertas visibles y acciones contextuales. |
| Tablas basicas | Filtros, densidad, ordenamiento y legibilidad pueden mejorar. |
| Feedback limitado | `confirm()` nativo y mensajes simples no se sienten modernos. |
| Dependencia de CDN | Puede afectar performance, disponibilidad y consistencia visual. |
| Sistema visual parcial | Hay variables CSS, pero falta una capa mas completa de tokens, sombras, estados y movimiento. |

---

## 2. Componentes utiles detectados en Plantilla Dashboard

### Componentes principales

| Componente plantilla | Archivo / referencia | Uso recomendado en Cohort Monitor |
|----------------------|----------------------|-----------------------------------|
| Dashboard base | `src/html/index.html` | Inspiracion para KPIs con mini graficas, layout de analitica y bloques secundarios. |
| Widgets | `src/html/widgets.html` | Cards compactas con avatar/icono, tendencia y sparkline. |
| Cards | `src/html/cards.html` | Estandarizar cards, headers, footers, hover y densidad. |
| Data tables | `src/html/data-tables.html` | Tablas de cohortes, alertas, usuarios y reportes. |
| GridJS | `src/html/grid-tables.html` | Alternativa liviana para busqueda, ordenamiento y paginacion. |
| ApexCharts | `src/html/apex-*.html`, `src/assets/js/apexcharts-*.js` | Graficas de admisiones, estados, tipos de bootcamp y riesgo. |
| Chart.js | `src/html/chartjs-charts.html`, `src/assets/js/index.js` | Mini charts y sparklines en cards. |
| SweetAlert2 | `src/html/sweet_alerts.html` | Confirmaciones modernas para eliminar, logout, importacion y cambios criticos. |
| Toasts | `src/html/toasts.html` | Feedback no intrusivo despues de acciones. |
| Tooltips/Popovers | `src/html/tooltips.html`, `src/html/popovers.html` | Ayuda contextual en metricas, estados y permisos. |
| Navs/Tabs | `src/html/navs_tabs.html` | Separar vistas por estado, tipo, area o rol. |
| Progress | `src/html/progress.html` | Barras segmentadas y progreso con estados semanticos. |
| Avatars | `src/html/avatars.html` | Usuarios, coaches, autores de comentarios y responsables. |
| Forms | `src/html/form_layout.html`, `form_inputs.html`, `floating_labels.html` | Mejorar creacion/edicion de cohortes. |
| Select2 / Choices | `src/html/form_select2.html`, `form_select.html` | Filtros y selects con busqueda. |
| Date/time pickers | `src/html/form_dateTime_pickers.html` | Fechas de cohorte, admisiones y filtros. |
| File upload | `src/html/form_file_uploads.html` | Importacion Excel/CSV con mejor estado de carga. |
| FullCalendar | `src/html/full-calendar.html` | Calendario de cohortes/coaches. |
| Loader | `src/html/partials/loader.html` | Estados de carga y transiciones iniciales. |
| Switcher | `src/html/partials/switcher.html`, `_switcher.scss` | Base conceptual para tema claro/oscuro y modo compacto. |
| Sidebar avanzado | `src/html/partials/sidebar.html` | Categorias, submenus, scroll interno y estados activos. |

### Librerias de mayor valor

| Libreria | Prioridad | Razon |
|----------|-----------|-------|
| ApexCharts | Alta | Mejora inmediata de visibilidad de datos operativos. |
| SweetAlert2 | Alta | Sustituye confirmaciones nativas y mejora confianza. |
| GridJS o DataTables | Alta | Hace mas utiles las vistas con volumen de registros. |
| Choices.js o Select2 | Media | Mejora filtros y formularios largos. |
| Flatpickr | Media | Mejora captura y filtrado por fechas. |
| FilePond / Dropzone | Media | Mejora importacion masiva. |
| FullCalendar | Media | Potencia vista de coaches y cohortes por fechas. |
| SimpleBar | Baja | Mejora scroll de sidebar/listas, no es critico. |
| Swiper | Baja | Usarlo solo si se requiere carrusel de resumen, no como decoracion. |

---

## 3. Principios de modernizacion

1. Adoptar componentes por necesidad funcional, no copiar toda la plantilla.
2. Mantener Bootstrap 5 como base para evitar una reescritura grande.
3. Crear una capa de design tokens en `app.css`: color, sombra, radio, espacios, z-index, transiciones y estados.
4. Convertir patrones repetidos en clases reutilizables: `app-card`, `metric-card`, `data-panel`, `toolbar`, `empty-state`, `status-pill`.
5. Priorizar legibilidad de datos sobre decoracion.
6. Agregar movimiento sutil: hover, focus, loading, entrada progresiva y feedback, evitando animaciones pesadas.
7. Diseñar primero para operacion diaria: escaneo rapido, comparacion, filtros, alertas y acciones claras.
8. Mantener accesibilidad: contraste, focus visible, labels, ARIA en controles, soporte teclado.
9. Mejorar performance: assets locales, carga diferida de librerias por pagina y CSS/JS modular.

---

## 4. Roadmap por fases

## Fase 0: Preparacion tecnica

### Objetivo
Crear una base ordenada para integrar componentes de la plantilla sin romper las vistas actuales.

### Acciones

| Tarea | Archivo destino | Detalle |
|------|-----------------|---------|
| Inventariar assets necesarios | `docs` | Listar CSS/JS exactos a copiar desde la plantilla. |
| Crear carpeta vendor local | `public/assets/vendor/` | Copiar solo librerias elegidas, no toda la plantilla. |
| Definir carga por pagina | `layouts/main.php` | Permitir `$styles` y `$scripts` opcionales por vista. |
| Normalizar encoding visible | Vistas y docs | Corregir textos mojibake como `Cohortes`, `Acciones Rapidas`, `Cerrar sesion`. |
| Crear tokens CSS | `public/assets/css/app.css` | Variables para colores, sombras, radios, transiciones y superficies. |
| Crear modulo JS UI | `public/assets/js/app.js` | Agregar inicializadores por `data-*`: toasts, modals, confirmaciones, charts. |

### Criterio de aceptacion

- La app sigue funcionando igual.
- Las vistas pueden inyectar CSS/JS especifico sin cargar todo en todas las paginas.
- No hay dependencias nuevas globales innecesarias.

---

## Fase 1: Sistema visual moderno

### Objetivo
Elevar la percepcion visual de toda la app con una capa de UI consistente.

### Mejoras propuestas

| Area | Cambio |
|------|--------|
| Superficies | Cards con borde suave, sombra controlada, hover sutil y header consistente. |
| Paleta | Mantener identidad Kodigo, pero sumar tokens semanticos: success, warning, danger, info, neutral. |
| Tipografia | Mejor jerarquia: titulos compactos, metricas grandes, labels secundarios. |
| Iconografia | Unificar Bootstrap Icons y, si se importa Tabler desde plantilla, usarlo solo en nuevas piezas. |
| Estados | `status-pill` para cohortes, marketing, riesgo, completado, pendiente. |
| Animacion | Transiciones de 150-250ms en hover, sidebar, dropdowns, progress y cards. |
| Focus | Estados visibles para teclado en links, botones y formularios. |
| Empty states | Icono, mensaje claro y accion primaria cuando aplique. |

### Componentes a tomar como referencia

- `widgets.html`: cards compactas con avatar y tendencia.
- `cards.html`: variantes de card.
- `progress.html`: barras de progreso mas refinadas.
- `badge.html`: badges/pills por estado.
- `avatars.html`: avatares y responsables.

### Entregables

- Nueva seccion de tokens en `app.css`.
- Clases base:
  - `.app-shell`
  - `.app-card`
  - `.app-card-header`
  - `.metric-card`
  - `.metric-icon`
  - `.metric-trend`
  - `.status-pill`
  - `.ui-toolbar`
  - `.surface-muted`
  - `.skeleton`

---

## Fase 2: Dashboard ejecutivo

### Objetivo
Convertir `/` en una vista ejecutiva con mejor visibilidad de salud, progreso y riesgo.

### Nuevo layout recomendado

1. Header contextual:
   - Saludo compacto.
   - Fecha.
   - Rol.
   - Estado general del sistema: `Sin alertas criticas`, `X alertas activas`, `Y cohortes inician pronto`.

2. KPI row avanzado:
   - Total cohortes.
   - En progreso.
   - Completadas.
   - Alertas activas.
   - Cada card con:
     - Icono.
     - Valor principal.
     - Microcopy.
     - Mini grafica/sparkline.
     - Tendencia o comparacion cuando exista data.

3. Panel principal de admisiones:
   - Grafica area/linea con progreso mensual.
   - Meta total vs admisiones reales.
   - Segmentacion B2B/B2C.

4. Panel de estado de cohortes:
   - Donut chart con estados.
   - Lista corta por estado con porcentaje y color.

5. Panel de riesgo:
   - Alertas por severidad.
   - Etapas de marketing en riesgo.
   - Comentarios recientes.
   - CTA claro a `/alerts`.

6. Cohortes proximas:
   - Cards compactas o tabla liviana.
   - Fecha de inicio, coach, tipo, progreso de admision.
   - Indicador visual si faltan menos de 7/15/30 dias.

### Componentes plantilla aplicables

| Necesidad | Componente plantilla |
|-----------|----------------------|
| KPIs con mini chart | `index.html`, `widgets.html`, `src/assets/js/index.js` |
| Grafica de admisiones | `apex-area-charts.html`, `apex-column-charts.html` |
| Estados de cohortes | `apex-pie-charts.html`, `apex-radialbar-charts.html` |
| Riesgo por etapa | `apex-bar-charts.html`, `listgroup.html`, `badge.html` |
| Proximos inicios | `cards.html`, `timeline.html`, `tables.html` |

### Datos requeridos desde backend

| Dato | Uso |
|------|-----|
| Cohortes por mes | Grafica de tendencia. |
| Admisiones B2B/B2C por mes | Grafica stacked o area. |
| Cohortes por estado | Donut y resumen. |
| Alertas por tipo/severidad | Panel de riesgo. |
| Proximos inicios | Lista priorizada. |
| Bootcamps por tipo | Distribucion y filtros. |

### Criterios de aceptacion

- En 5 segundos el usuario entiende: volumen, progreso, riesgo y proximos eventos.
- Las cards no dependen solo de color para comunicar estado.
- Mobile muestra primero KPIs, luego riesgo, luego graficas/tablas.
- Las graficas tienen fallback textual cuando no hay datos.

---

## Fase 3: Navegacion, header y shell

### Objetivo
Hacer que la navegacion se sienta mas moderna y orientada al trabajo diario.

### Sidebar

Tomar ideas de `partials/sidebar.html`:

| Mejora | Detalle |
|--------|---------|
| Categorias | Agrupar: Principal, Operacion, Analitica, Administracion, Cuenta. |
| Submenus | Reservar para Reportes, Administracion o futuras secciones. |
| Indicadores | Badge de alertas junto a `Alertas`. |
| Scroll interno | Sidebar estable con scroll limpio para pantallas pequenas. |
| Estado colapsado | Tooltips solo cuando esta colapsado. |
| Active state | Marcador visual mas claro con barra lateral y fondo sutil. |

### Header

| Mejora | Detalle |
|--------|---------|
| Busqueda global | Buscar cohortes por codigo/nombre desde el header. |
| Notificaciones | Dropdown con alertas recientes y riesgo. |
| Acciones contextuales | Boton segun pagina: nueva cohorte, exportar, importar, limpiar filtros. |
| Modo compacto | Toggle persistido para usuarios operativos. |
| Perfil | Avatar con iniciales, rol y menu mejorado. |

### Componentes plantilla aplicables

- `partials/header.html`
- `partials/sidebar.html`
- `dropdowns.html`
- `avatars.html`
- `notifications.html`
- `tooltips.html`

---

## Fase 4: Tablas, filtros y densidad operativa

### Objetivo
Mejorar la eficiencia de pantallas con listas: Cohortes, Alertas, Usuarios, Marketing y Reportes.

### Cohortes

| Mejora | Detalle |
|--------|---------|
| Toolbar superior | Busqueda, filtros principales, exportar, nuevo registro. |
| Filtros como chips | Mostrar filtros activos con opcion de remover. |
| Tabla densa | Columnas escaneables: codigo, tipo, coach, fechas, admisiones, estado, acciones. |
| Ordenamiento | Por inicio, estado, admisiones, coach. |
| Columnas responsive | En mobile convertir filas en cards compactas. |
| Acciones iconicas | Ver, editar, marketing, reportar, con tooltips. |
| Estados visuales | Pills consistentes y barra de progreso. |

### Alertas

| Mejora | Detalle |
|--------|---------|
| Severidad | Critica, alta, media, baja o riesgo actual. |
| Agrupacion | Por cohorte o por tipo de alerta. |
| CTA claro | Resolver, revisar marketing, ver cohorte. |
| Timeline | Historial reciente de riesgo por cohorte. |

### Usuarios

| Mejora | Detalle |
|--------|---------|
| Avatares | Iniciales y rol visible. |
| Estado | Activo/inactivo con pill. |
| Acciones | Reset password, editar, activar/desactivar con SweetAlert. |

### Libreria recomendada

Elegir una opcion:

| Opcion | Pros | Contras |
|--------|------|---------|
| GridJS | Ligera, moderna, buena UX para tablas medianas. | Requiere adaptar datos o inicializar desde tabla HTML. |
| DataTables BS5 | Muy completa, conocida, exportable con plugins. | Mas pesada y visualmente mas clasica. |

Recomendacion inicial: GridJS para nuevas tablas interactivas y mantener tablas Bootstrap donde el backend ya pagina o filtra.

---

## Fase 5: Formularios modernos

### Objetivo
Reducir friccion en creacion/edicion/importacion de cohortes.

### Mejoras

| Area | Cambio |
|------|--------|
| Inputs | Labels claros, helper text, errores inline, focus visible. |
| Selects | Choices.js o Select2 para coach, bootcamp, proyecto, estado. |
| Fechas | Flatpickr para fechas con formato consistente. |
| Secciones | Agrupar: Identidad, Fechas, Admisiones, Asignacion, Estado. |
| Validacion | Mensajes especificos, no solo validacion generica del navegador. |
| Importacion | FilePond/Dropzone con drag and drop, preview de archivo y estado de carga. |
| Confirmaciones | SweetAlert para cambios sensibles. |

### Componentes plantilla aplicables

- `form_layout.html`
- `form_inputs.html`
- `floating_labels.html`
- `form_select2.html`
- `form_dateTime_pickers.html`
- `form_file_uploads.html`
- `form_validation.html`
- `sweet_alerts.html`

---

## Fase 6: Calendario y timeline

### Objetivo
Potenciar la visibilidad temporal de cohortes y coaches.

### Mejoras

| Vista | Cambio |
|-------|--------|
| Coaches | Integrar FullCalendar para ver carga por fecha. |
| Cohortes | Calendario de inicios, cierres, 50%, 75% y deadline de admision. |
| Dashboard | Mini timeline de proximos hitos. |
| Tooltips | Detalle al pasar sobre evento: bootcamp, coach, meta, admisiones, estado. |
| Filtros | Por tipo, coach, proyecto, estado. |

### Componentes plantilla aplicables

- `full-calendar.html`
- `timeline.html`
- `timeline2.html`
- `tooltips.html`
- `popovers.html`

---

## Fase 7: Feedback, estados y microinteracciones

### Objetivo
Hacer que la app responda con claridad a cada accion del usuario.

### Cambios

| Patron | Implementacion |
|--------|----------------|
| Confirmaciones | SweetAlert2 para eliminar, desactivar, resetear password, importar. |
| Exito/error | Toasts para acciones guardadas, importaciones, exportaciones. |
| Carga | Skeletons en cards/tablas cuando aplique. |
| Hover | Elevacion sutil en cards clicables y filas importantes. |
| Empty states | Accion recomendada cuando no hay datos. |
| Disabled states | Motivo visible via tooltip si una accion no esta disponible. |
| Animacion de progreso | Barras con transicion al renderizar. |

### Componentes plantilla aplicables

- `sweet_alerts.html`
- `toasts.html`
- `spinners.html`
- `placeholders.html`
- `tooltips.html`

---

## Fase 8: Tema, modo compacto y personalizacion

### Objetivo
Dar una experiencia mas moderna y adaptable sin distraer al usuario.

### Cambios propuestos

| Feature | Detalle |
|---------|---------|
| Tema claro/oscuro | Inspirado en `switcher.html`, persistido en `localStorage`. |
| Modo compacto | Reduce padding de cards, tablas y header para usuarios avanzados. |
| Sidebar colapsado | Ya existe, pero mejorar tooltips, animacion y active state. |
| Preferencias por usuario | Inicialmente `localStorage`; luego persistir en perfil si se requiere. |
| Respeto a sistema | Leer `prefers-color-scheme` como default opcional. |

### Nota tecnica

La plantilla trae un switcher amplio. No conviene copiarlo completo al inicio. Se recomienda implementar solo:

- `data-theme="light|dark"` en `html` o `body`.
- `data-density="comfortable|compact"`.
- Variables CSS para superficies, texto, bordes y sombras.

---

## Fase 9: Accesibilidad, responsive y calidad visual

### Checklist obligatorio

| Area | Criterio |
|------|----------|
| Contraste | Texto normal >= 4.5:1, texto grande >= 3:1. |
| Teclado | Navegacion completa por tab en sidebar, header, tablas y modales. |
| Focus | Visible y consistente. |
| Tooltips | No depender de tooltip para informacion critica. |
| Graficas | Fallback textual y labels accesibles. |
| Mobile | No overlap, no texto cortado, botones tactiles de al menos 40px. |
| Tablas | Scroll claro o vista card en mobile. |
| Estados | Icono + texto + color, no solo color. |
| Motion | Respetar `prefers-reduced-motion`. |

---

## 5. Arquitectura CSS/JS recomendada

### Estructura propuesta

```text
public/
  assets/
    css/
      app.css
      dashboard.css          # opcional si el dashboard crece mucho
    js/
      app.js
      dashboard.js           # charts y widgets del dashboard
      tables.js              # inicializadores de tablas
      forms.js               # selects, datepickers, upload
    vendor/
      apexcharts/
      sweetalert2/
      gridjs/
      choices/
      flatpickr/
      fullcalendar/
```

### Carga por vista

Modificar `app/Views/layouts/main.php` para soportar:

```php
<?php foreach (($styles ?? []) as $href): ?>
    <link href="<?= htmlspecialchars($href) ?>" rel="stylesheet">
<?php endforeach; ?>

<?php foreach (($scripts ?? []) as $src): ?>
    <script src="<?= htmlspecialchars($src) ?>"></script>
<?php endforeach; ?>
```

Esto permite que ApexCharts cargue solo en dashboard/reportes, FullCalendar solo en coaches, y FilePond solo en importacion.

---

## 6. Priorizacion recomendada

### Sprint 1: Base visual + dashboard

| Prioridad | Tarea |
|-----------|-------|
| Alta | Crear tokens CSS y componentes base. |
| Alta | Mejorar cards KPI con estilo `widget` y tendencia. |
| Alta | Integrar ApexCharts en dashboard. |
| Alta | Reemplazar barras simples por graficas de estado/admisiones. |
| Media | Mejorar welcome/header contextual. |

### Sprint 2: Tablas y filtros

| Prioridad | Tarea |
|-----------|-------|
| Alta | Mejorar `/cohorts` con toolbar, chips de filtros y tabla densa. |
| Alta | Mejorar `/alerts` con severidad, agrupacion y CTAs. |
| Media | Evaluar GridJS en cohortes o reportes. |
| Media | Mejorar acciones iconicas con tooltips. |

### Sprint 3: Formularios e importacion

| Prioridad | Tarea |
|-----------|-------|
| Alta | Mejorar formularios create/edit con secciones claras. |
| Media | Integrar Choices.js/Select2 para selects largos. |
| Media | Integrar Flatpickr para fechas. |
| Media | Mejorar importacion con FilePond/Dropzone. |

### Sprint 4: Shell avanzado y feedback

| Prioridad | Tarea |
|-----------|-------|
| Alta | Reemplazar `confirm()` por SweetAlert2. |
| Alta | Implementar toasts globales. |
| Media | Notificaciones en header. |
| Media | Sidebar con categorias e indicadores. |
| Baja | Tema oscuro y modo compacto. |

### Sprint 5: Calendario y experiencia avanzada

| Prioridad | Tarea |
|-----------|-------|
| Media | FullCalendar para coaches/cohortes. |
| Media | Timeline de hitos operativos. |
| Baja | Preferencias persistidas por usuario. |

---

## 7. Backlog detallado por pantalla

### Dashboard `/`

- Redisenar KPI cards usando patron de `widgets.html`.
- Agregar sparklines o mini charts.
- Agregar ApexCharts:
  - Area: admisiones vs meta.
  - Donut: estados de cohortes.
  - Bar: bootcamps por tipo.
  - Bar horizontal: alertas por etapa de marketing.
- Reorganizar dashboard por prioridad operativa:
  - Resumen.
  - Riesgo.
  - Progreso.
  - Proximos hitos.
  - Cohortes recientes.
- Mejorar empty states.
- Agregar skeleton/loading si una seccion carga asincrona en el futuro.

### Cohortes `/cohorts`

- Toolbar con busqueda, filtros y accion primaria.
- Chips de filtros activos.
- Tabla densa con estados visuales.
- Vista card para mobile.
- Ordenamiento y paginacion si el volumen crece.
- Acciones por icono con tooltip.
- Indicador de proximidad a fecha de inicio/deadline.

### Detalle de cohorte `/cohorts/{id}`

- Header tipo entity page: codigo, estado, bootcamp, coach, fechas.
- Cards de resumen: admisiones, progreso, marketing, calendario.
- Timeline de hitos.
- Tabs: Resumen, Marketing, Comentarios, Historial.
- CTAs visibles segun rol.

### Marketing `/marketing`

- Vista tablero por etapas.
- Estados por etapa con pills.
- Alertas de riesgo con severidad.
- Grafica de cohortes por etapa.
- Mejor historial visual con timeline.

### Alertas `/alerts`

- Resumen superior: total, criticas, marketing, comentarios de riesgo.
- Filtros por severidad, tipo, cohorte, fecha.
- Lista agrupada por cohorte.
- Accion para revisar/resolver.

### Coaches `/coaches`

- Mantener timeline actual, pero mejorar densidad y tooltips.
- Agregar alternativa FullCalendar.
- Filtros por coach, bootcamp, estado y rango de fechas.
- Cards de carga por coach.

### Usuarios `/users`

- Cards/resumen por rol y estado.
- Tabla con avatar, rol, ultimo acceso si existe, estado.
- Confirmaciones SweetAlert para reset/desactivar.
- Filtros por rol/estado.

### Importacion `/cohorts/import`

- Drag and drop.
- Estado de validacion del archivo.
- Resumen antes de confirmar: filas validas, errores, duplicados.
- Toast de resultado.
- Tabla de errores con descarga opcional.

### Login `/login`

- Mantener simple.
- Mejorar visual con card limpia, marca y microinteraccion.
- Estados claros de error.
- Mostrar/ocultar password con icono.

---

## 8. Metricas de exito

| Metrica | Objetivo |
|---------|----------|
| Tiempo para entender estado general | Menos de 5 segundos en dashboard. |
| Accesos a alertas desde dashboard | Aumentar uso de `/alerts`. |
| Reduccion de clicks para encontrar cohortes | Busqueda/filtros visibles en primer viewport. |
| Errores de formularios | Mensajes mas claros y menos reintentos. |
| Uso mobile | Tablas legibles sin zoom horizontal excesivo. |
| Performance | Mantener carga inicial ligera cargando librerias por vista. |
| Consistencia visual | Misma jerarquia, espacios y estados en todas las pantallas. |

---

## 9. Riesgos y mitigaciones

| Riesgo | Mitigacion |
|--------|------------|
| Copiar demasiada plantilla | Adoptar componentes uno por uno y borrar assets no usados. |
| Aumentar peso del frontend | Cargar librerias por vista, no globalmente. |
| Romper Bootstrap actual | Mantener clases actuales y crear nuevas clases compatibles. |
| Inconsistencia visual | Definir tokens y componentes antes de redisenar pantallas. |
| Graficas sin datos suficientes | Crear fallback textual y validar datos en backend. |
| Accesibilidad pobre por animaciones | Usar motion sutil y `prefers-reduced-motion`. |
| Duplicar icon libraries | Mantener Bootstrap Icons como default; agregar Tabler solo si aporta valor real. |

---

## 10. Primera implementacion recomendada

Para empezar los cambios con impacto visible y bajo riesgo:

1. Preparar layout para `$styles` y `$scripts` por vista.
2. Copiar ApexCharts y SweetAlert2 a `public/assets/vendor`.
3. Agregar tokens visuales y nuevas clases base en `app.css`.
4. Redisenar solo los KPI cards del dashboard usando patron `widgets.html`.
5. Agregar dos graficas al dashboard:
   - Admisiones vs meta.
   - Estado de cohortes.
6. Sustituir confirmaciones nativas por SweetAlert2.
7. Validar responsive desktop/tablet/mobile.

Este primer bloque mejora visibilidad, modernidad y confianza sin tocar flujos criticos de negocio.

---

## 11. Definition of Done por cambio frontend

Cada cambio visual debe cumplir:

- No rompe permisos por rol.
- Funciona en desktop, tablet y mobile.
- Mantiene contraste suficiente.
- Tiene estado vacio, estado con datos y estado de error cuando aplique.
- No carga librerias innecesarias en paginas que no las usan.
- No introduce texto cortado u overlap.
- Mantiene nombres de rutas y acciones existentes.
- Incluye prueba manual del flujo afectado.
- Si agrega JS, no bloquea la pagina cuando el elemento no existe.

---

## 12. Registro de avance

| Bloque | Estado | Entregable |
|--------|--------|------------|
| Base tecnica | Completado | Assets locales, carga por vista, SweetAlert2, ApexCharts y tokens visuales. |
| Dashboard ejecutivo | Completado | KPI cards, graficas, paneles de riesgo, acciones rapidas y proximas cohortes. |
| Navegacion y busqueda | Completado | Header con busqueda global de cohortes, sidebar categorizado y filtros visibles. |
| Alertas | Completado | Workbench de riesgo con resumen, filtros cliente y paneles accionables. |
| Cohortes listado | Completado | Hero, filtros, resumen, tabla desktop y cards mobile. |
| Formularios e importacion | Completado | Heroes, paneles de formulario, upload zone y feedback moderno. |
| Detalle de cohorte y marketing | Completado | Entity page con KPIs, timeline, admisiones, comentarios y tablero de etapas marketing. |
| Calendario/coaches | Completado | Hero operativo, KPIs, filtros, timeline Gantt refinado, lista agrupada y cards mobile. |
| Login/perfil | Completado | Login moderno con password toggle, hero de producto, perfil self-service, seguridad y resumen de cuenta. |
| Reportes | Completado | Workbench ejecutivo con filtros, KPIs, resumen por area/estado, tabla desktop, cards mobile y exportaciones. |
| Usuarios | Completado | Gestion admin con KPIs, roles, directorio responsive, cards mobile y formularios modernos. |
| Modo compacto y accesibilidad | Completado | Toggle persistente en header, densidad compacta global, focus visible y reduced motion transversal. |
| QA responsive final | Completado | Skip link, landmark main, busqueda mobile, atajo Ctrl/Cmd+K, aria-live y footer depurado. |
| Siguiente fase sugerida | Pendiente | Revision visual en navegador o limpieza/refactor de CSS acumulado. |
